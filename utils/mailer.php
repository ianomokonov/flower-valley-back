<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

class Mailer
{

    //Create an instance; passing `true` enables exceptions
    public $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer;
        try {
            //Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $this->mail->isSMTP();                                            //Send using SMTP
            $this->mail->Host       = 'smtp.mail.ru';                     //Set the SMTP server to send through
            $this->mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $this->mail->Username   = 'flowervalley@mail.ru';                     //SMTP username
            $this->mail->Password   = 'r25qFNqRmdqRm21si2XM';                               //SMTP password
            $this->mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
            $this->mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            $this->mail->CharSet = 'UTF-8';
            //Recipients
            $this->mail->From = 'flowervalley@mail.ru';
            $this->mail->FromName = 'Агрофима Цветочная Долина';
            //$this->mail->addAddress('lepingrapes@yandex.ru');
            // $mail->addCC('cc@example.com');
            // $mail->addBCC('bcc@example.com');

            //Attachments
            // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $this->mail->isHTML(true);                                  //Set email format to HTML
            // $mail->Subject = 'Here is the subject';
            // $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
            // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
