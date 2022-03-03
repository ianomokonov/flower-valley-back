<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/mailer.php';

abstract class Message
{
    protected $mailer;

    public function __construct()
    {
        $this->mailer = new Mailer();
    }

    public abstract function send($request, $files);

    protected function setFiles($files)
    {
        foreach (array_keys($files) as $key) {
            $this->mailer->mail->addAttachment($files[$key]['tmp_name'], $files[$key]['name']);
        }
    }
}

class Individual extends Message
{
    public function send($request, $files)
    {
        $this->mailer->mail->Subject = "ЗАКАЗ С САЙТА flowervalley.ru №" . $request['orderId'];

        $message = "
        Ваш заказ принят. Для скорейшего резервирования на нужную Вам дату требуется предоплата в размере от 30% до 100% суммы заказа. Оплата на карту Сбербанка 4274 2700 1927 5403, держатель карты Олег Валентинович Б. 
        Просьба ничего не указывать в назначении платежа. 
        Чек об оплате отправить на эту эл.почту ответным письмом или в Вацап по номеру +79151091000
        ";

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress($request['email']);
        $this->setFiles($files);
        $this->mailer->mail->send();
    }
}

class Business extends Message
{
    public function send($request, $files)
    {
        $this->mailer->mail->Subject = "Счет №" . $request['billNumber'] . " от " . $request['billDate'];

        $message = "Ваша заявка принята. ";

        if (isset($request['requestNumber']) && $request['requestNumber']) {
            $message = "Заказ по запросу " . $request['requestNumber'] . " сформирован. ";
        }

        $message .= "Выставляем счет к оплает. 
        Просим сообщить об оплате на нашу эл. почту или в Вацап по номеру 8(915)109-10-00 
        для скорейшего резервирования Вашего заказа на нужную Вам дату";

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress($request['email']);
        $this->setFiles($files);
        $this->mailer->mail->send();
    }
}

class Admin extends Message
{
    public function send($request, $files)
    {
        $client = $request['isBusiness'] ? 'ЮР' : 'ФИЗ';
        $this->mailer->mail->Subject = "[$client].[" . $request['sum'] . "].[" . $request['orderId'] . '].[' . $request['clientName'] . ']';

        $message = "
        <a href='" . DataBase::$host . 'admin/orders/' . $request['orderId'] . "'>Ссылка на заказ</a>
        ";

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress($request['email']);
        $this->setFiles($files);
        $this->mailer->mail->send();
    }
}

class PriceList extends Message
{
    public function send($request, $files)
    {
        $this->mailer->mail->Subject = "Прайс-лист Агрофирмы Цветочная Долина";

        $message = "
        По Вашему запросу предоставлен прайс-лист
        ";

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress($request['email']);
        $this->setFiles($files);
        $this->mailer->mail->send();
    }
}

class OrderEdited extends Message
{
    public function send($request, $files)
    {
        $this->mailer->mail->Subject = "ЗАКАЗ С САЙТА flowervalley.ru №" . $request['orderId'];

        $message = "
        Ваш заказ отредактирован. Подробности в прикрепленном файле.
        ";

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress($request['email']);
        $this->setFiles($files);
        $this->mailer->mail->send();
    }
}
