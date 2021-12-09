<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class Token
{

    // переменные, используемые для JWT 
    private $authKey = "bj628KEx85";
    private $refreshKey = "bO62L3gj9xo5";
    private $iss = "http://any-site.org";
    private $aud = "http://any-site.com";
    private $iat;
    private $nbf;

    public function __construct()
    {
        // установить часовой пояс по умолчанию 
        date_default_timezone_set('Europe/Moscow');
    }

    public function decode($jwt, $isRefresh = false)
    {
        // если JWT не пуст 
        if ($jwt) {

            // если декодирование выполнено успешно, показать данные пользователя 
            try {
                // декодирование jwt 
                if ($isRefresh) {
                    
                    $decoded = JWT::decode($jwt, $this->refreshKey, array('HS256'));
                } else {
                    $decoded = JWT::decode($jwt, $this->authKey, array('HS256'));
                }
                return $decoded;
            }

            // если декодирование не удалось, это означает, что JWT является недействительным 
            catch (ExpiredException $e) {
                echo json_encode($e->getMessage());
                // сообщить пользователю отказано в доступе и показать сообщение об ошибке 
                throw new Exception("Unauthorized", 401);
            }
        }

        // показать сообщение об ошибке, если jwt пуст 
        else {

            // сообщить пользователю что доступ запрещен 
            throw new Exception("Bad request", 400);
        }
    }

    public function encode($data)
    {

        $this->nbf = time();
        $this->iat = $this->iat + 10 * 60 * 60;
        $token = array(
            "iss" => $this->iss,
            "aud" => $this->aud,
            "iat" => $this->iat,
            "nbf" => $this->nbf,
            "data" => $data
        );

        $refreshTokenData = array(
            "iss" => $this->iss,
            "aud" => $this->aud,
            "nbf" => $this->nbf,
            "data" => $data
        );

        try {
            // декодирование jwt 
            $token = JWT::encode($token, $this->authKey);
            $refreshToken = JWT::encode($refreshTokenData, $this->refreshKey);
            return array("token" => $token, "refreshToken" => $refreshToken);
        }

        // если декодирование не удалось, это означает, что JWT является недействительным 
        catch (Exception $e) {

            // сообщить пользователю отказано в доступе и показать сообщение об ошибке 
            throw new Exception("Unauthorized", 401);
        }
    }
}
