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

    public function create($userData)
    {
        $userData = (object) $this->dataBase->stripAll((array)$userData);

        // Вставляем запрос
        $userData->password = password_hash($userData->password, PASSWORD_BCRYPT);

        if ($this->EmailExists($userData->email)) {
            throw new Exception('Пользователь уже существует');
        }
        $query = $this->dataBase->genInsertQuery(
            $userData,
            $this->table
        );

        // подготовка запроса
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0] != null) {
            $stmt->execute($query[1]);
        }
        $userId = $this->dataBase->db->lastInsertId();
        if ($userId) {
            $tokens = $this->token->encode(array("id" => $userId));
            $this->addRefreshToken($tokens["refreshToken"], $userId);
            return $tokens;
        }
        return null;
    }

    // Получение пользовательской информации
    public function read($userId)
    {
        $query = "SELECT email, phone, isAdmin FROM $this->table WHERE id=$userId";
        $user = $this->dataBase->db->query($query)->fetch();
        $user['isAdmin'] = $user['isAdmin'] == '1';
        return $user;
    }

    // Получение пользовательской информации

    public function update($userId, $userData, $image = null)
    {
    }

    public function getUserTasks($userId)
    {
        $query = "SELECT id, name, isDone FROM UserTask WHERE userId=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($userId));
        $tasks = [];
        while ($task = $stmt->fetch()) {
            $task['id'] = $task['id'] * 1;
            $task['isDone'] = $task['isDone'] == '1';
            $tasks[] = $task;
        }
        return $tasks;
    }

    public function addUserTask($userId, $request)
    {
        $query = "INSERT INTO UserTask (userId, name) VALUES (?, ?)";
        $query = $this->dataBase->db->prepare($query);
        $query->execute(array($userId, $request['name']));
        return $this->dataBase->db->lastInsertId();
    }

    public function removeUserTask($userTaskId)
    {
        $query = "DELETE FROM UserTask WHERE id = ?";
        $this->dataBase->db->prepare($query)->execute(array($userTaskId));
    }

    public function setUserScripts($request)
    {
        $query = "DELETE FROM UserScript WHERE userId=?";
        $this->dataBase->db->prepare($query)->execute(array($request['userId'])); 
        $query = "INSERT INTO UserScript (userId, scriptId) VALUES";
        $props = [];
        foreach ($request['scriptIds'] as $scriptId) {
            $query = $query . " (?,?),";
            $props[] = $request['userId'];
            $props[] = $scriptId;
        }
        $query  = rtrim($query, ',');
        $this->dataBase->db->prepare($query)->execute($props);
    }

    public function getUsers()
    {
        $query = "SELECT id, email, phone, isAdmin FROM " . $this->table . " WHERE isAdmin=0";
        $stmt = $this->dataBase->db->query($query);
        $users = [];
        while ($user = $stmt->fetch()) {
            $user['id'] = $user['id'] * 1;
            $user['isAdmin'] = $user['isAdmin'] == '1';
            $user['scriptIds'] = $this->getUserScriptsIds($user['id']);
            $users[] = $user;
        }
        return $users;
    }

    public function getUserScriptsIds($userId)
    {
        $query = "SELECT scriptId FROM UserScript WHERE userId=?";
        $query = $this->dataBase->db->prepare($query);
        $query->execute(array($userId));
        $scriptIds = [];
        while ($script = $query->fetch()) {
            $scriptIds[] = $script['scriptId'] * 1;
        }
        return $scriptIds;
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

    public function getUserImage($userId)
    {
        $query = "SELECT image FROM $this->table WHERE id = $userId";
        $stmt = $this->dataBase->db->query($query);

        return $stmt->fetch()['image'];
    }

    public function login($email, $password)
    {
        if ($email != null) {
            $sth = $this->dataBase->db->prepare("SELECT id, password, isAdmin FROM " . $this->table . " WHERE email = ? LIMIT 1");
            $sth->execute(array($email));
            $fullUser = $sth->fetch();
            if ($fullUser) {
                if (!password_verify($password, $fullUser['password'])) {
                    throw new Exception("User not found", 404);
                }
                $tokens = $this->token->encode(array("id" => $fullUser['id'], "isAdmin" => $fullUser['isAdmin'] == '1'));
                $this->addRefreshToken($tokens["refreshToken"], $fullUser['id']);
                return $tokens;
            } else {
                throw new Exception("User not found", 404);
            }
        } else {
            return array("message" => "Введите данные для регистрации");
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

    // Обновление пароля
    public function updatePassword($userId, $password)
    {
        if ($userId) {
            $password = json_encode(password_hash($password, PASSWORD_BCRYPT));
            $query = "UPDATE $this->table SET password = '$password' WHERE id=$userId";
            $stmt = $this->dataBase->db->query($query);
        } else {
            return array("message" => "Токен неверен");
        }
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

    public function getUpdateLink($email)
    {
        // $userId = $this->LoginExists($email);
        // $path = 'logs.txt';

        // if (!$userId) {
        //     throw new Exception("Bad request", 400);
        // }

        // $tokens = $this->token->encode(array("id" => $userId));
        // $url = $this->baseUrl . "/update?updatePassword=" . urlencode($tokens[0]);
        // $subject = "Изменение пароля для jungliki.com";

        // $message = "<h2>Чтобы изменить пароль перейдите по ссылке <a href='$url'>$url</a>!</h2>";

        // $headers  = "Content-type: text/html; charset=utf-8 \r\n";

        // mail($email, $subject, $message, $headers);
        // file_put_contents($path, PHP_EOL . $email . " " . date("m.d.y H:i:s"), FILE_APPEND);
        // return true;
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
