<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/token.php';
require_once __DIR__ . '/../utils/filesUpload.php';
class User
{
    private $dataBase;
    private $table = 'User';
    private $token;
    private $fileUploader;
    // private $baseUrl = 'http://localhost:4200/back';
    private $baseUrl = 'http://stand1.progoff.ru/back';

    // конструктор класса User
    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->token = new Token();
        $this->fileUploader = new FilesUpload();
    }

    public function checkAdmin($userId)
    {
        $query = "SELECT isAdmin FROM $this->table WHERE id = $userId";
        $stmt = $this->dataBase->db->query($query);
        if ($stmt->fetch()['isAdmin'] == '1') {
            return true;
        }
        return false;
    }

    public function login($password)
    {
        $access = file("../utils/user.php");
        $passw = trim($access[1]);

        if ($password != null || !password_verify($password, $passw)) {
            if ($passw) {
                $tokens = $this->token->encode(array("id" => 1, "isAdmin" => true));
                $this->addRefreshToken($tokens["refreshToken"], 1);
                return $tokens;
            } else {
                throw new Exception("Wrong password", 401);
            }
        } else {
            throw new Exception("Wrong password", 401);
        }
    }

    public function isRefreshTokenActual($token, $userId)
    {
        $query = "SELECT id FROM RefreshTokens WHERE token = ? AND userId = ?";

        // подготовка запроса
        $stmt = $this->dataBase->db->prepare($query);
        // инъекция
        $token = htmlspecialchars(strip_tags($token));
        $userId = htmlspecialchars(strip_tags($userId));
        // выполняем запрос
        $stmt->execute(array($token, $userId));

        // получаем количество строк
        $num = $stmt->rowCount();

        if ($num > 0) {
            return true;
        }

        return $num > 0;
    }

    public function addRefreshToken($tokenn, $userId)
    {
        $query = "DELETE FROM RefreshTokens WHERE userId=$userId";
        $this->dataBase->db->query($query);
        $query = "INSERT INTO RefreshTokens (token, userId) VALUES ('$tokenn', $userId)";
        $this->dataBase->db->query($query);
    }

    public function removeRefreshToken($userId)
    {
        $query = "DELETE FROM RefreshTokens WHERE userId = $userId";
        $this->dataBase->db->query($query);
    }

    public function refreshToken($token)
    {
        $data = $this->token->decode($token, true)->data;

        if (!$this->isRefreshTokenActual($token, $data->id)) {
            throw new Exception("Unauthorized", 401);
        }
        $tokens = $this->token->encode((array)$data);
        $this->addRefreshToken($tokens["refreshToken"], $data->id);
        return $tokens;
    }

    private function EmailExists(string $email)
    {
        $query = "SELECT id FROM " . $this->table . " WHERE email = ?";


        // подготовка запроса
        $stmt = $this->dataBase->db->prepare($query);
        // выполняем запрос
        $stmt->execute(array($email));

        // получаем количество строк
        $num = $stmt->rowCount();

        if ($num > 0) {
            return true;
        }

        return false;
    }
}
