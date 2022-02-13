<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require_once '../vendor/autoload.php';

class Mailer
{

    //Create an instance; passing `true` enables exceptions
    public $mail;

    public function __construct()
    {
        // $this->mail = new PHPMailer(true);

        // try {
        //     //Server settings
        //     // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        //     // $mail->isSMTP();                                            //Send using SMTP
        //     // $mail->Host       = 'smtp.example.com';                     //Set the SMTP server to send through
        //     // $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        //     // $mail->Username   = 'user@example.com';                     //SMTP username
        //     // $mail->Password   = 'secret';                               //SMTP password
        //     // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        //     // $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //     //Recipients
        //     $this->mail->setFrom('info@progoff.ru', 'Цветочная долина');
        //     // $this->mail->addAddress('lepingrapes@yandex.ru');
        //     $this->mail->addReplyTo('info@progoff.ru', 'Цветочная долина');
        //     // $mail->addCC('cc@example.com');
        //     // $mail->addBCC('bcc@example.com');

        //     //Attachments
        //     // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
        //     // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

        //     //Content
        //     $this->mail->isHTML(true);                                  //Set email format to HTML
        //     // $mail->Subject = 'Here is the subject';
        //     // $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        //     // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        // } catch (Exception $e) {
        //     echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        // }
    }
}
