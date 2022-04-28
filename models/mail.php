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
        Здравствуйте!<br/>
        Ваш заказ принят.<br/>
        Номер Вашего заказа: "
        .$request['orderId'].
        "<br/>Для скорейшего резервирования на нужную Вам дату требуется предоплата в размере от 30% до 100% суммы заказа.<br/><br/>
        Оплата на карту Сбербанка 4274 2700 1927 5403, держатель карты Олег Валентинович Б.<br/>
        Просьба ничего не указывать в назначении платежа. Чек об оплате отправить на эту эл.почту ответным письмом или в Вацап по номеру +79151091000
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
        $this->mailer->mail->Subject = "Счет №" . $request['billNumber'] . " от " . $request['billDate'] . " к заказу №" . $request['orderId'];

        $message = "Здравствуйте!<br/>Ваша заявка принята.<br/>
        Номер Вашего заказа: "
        .$request['orderId'].
        "<br/>";

        if (isset($request['requestNumber']) && $request['requestNumber']) {
            $message = "Здравствуйте!<br/>Заказ по запросу " . $request['requestNumber'] . " сформирован.<br/>
        Номер Вашего заказа: "
        .$request['orderId'].
        "<br/>";
        }

        $message .= "Выставляем " . "<a href='https://375.ru/" . $request['accountNumber'] . "'>счет к оплате.</a><br/><br/>" .
        " Просим сообщить об оплате на нашу эл. почту или в Вацап по номеру 8(915)109-10-00 
        для скорейшего резервирования Вашего заказа на нужную Вам дату.";

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
        $client = $request['isBusiness'] == 'true' ? 'ЮР' : 'ФИЗ';
        $title = $request['isBusiness'] == 'true' ? 'ЮРЛИЦА' : 'ФИЗЛИЦА';
        $link = DataBase::$host . 'admin/orders/' . $request['orderId'];
        $this->mailer->mail->Subject = "[$client].[СУММА: " . $request['sum'] . "].[№" . $request['orderId'] . '].[' . $request['clientName'] . ']';
        $message = "
        Заказ для $title <br/><br/>
        Ссылка на заказ:<br/>
        <a href='$link'>$link</a><br/><br/>
        Клиент:<br/>
        ФИО: " . $request['contactName'] . "<br/>
        Почта: " . $request['contactEmail'] . "<br/>
        Телефон: " . $request['contactPhone'] . "<br/>
        Адрес доставки: " . $request['contactAddress'] . "<br/>
        ";

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress('9151091000@mail.ru');
        $this->mailer->mail->addAddress('lepingrapes@yandex.ru');
        //$this->mailer->mail->addAddress('i.a.volik@gmail.com');
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
        $this->mailer->mail->Subject = "ОБНОВЛЕН ЗАКАЗ С САЙТА flowervalley.ru №" . $request['orderId'];

        $message = "
        Ваш заказ отредактирован. Подробности в прикрепленном файле. 
        ";
        if (isset($request['accountNumber']) && $request['accountNumber']) {
            $message .= " Просьба оплатить <a href='https://375.ru/" . $request['accountNumber'] . "'>новый счет</a>";
        }

        $this->mailer->mail->Body = $message;
        $this->mailer->mail->addAddress($request['email']);
        $this->setFiles($files);
        $this->mailer->mail->send();
    }
}
