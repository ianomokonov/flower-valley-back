<?php
//класс базы данных
class DataBase
{
    private $dbname = "nomokoiw_scripts";
    private $login = "nomokoiw_scripts";
    private $password = "a33kvI&e";
    public $db;
    public function __construct()
    {
        $this->db = new PDO("mysql:host=localhost;dbname=" . $this->dbname . ";charset=UTF8", $this->login, $this->password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function genInsertQuery($ins, $t)
    {
        $ins = (array) $ins;
        $res = array('INSERT INTO ' . $t . ' (', array());
        $q = '';
        for ($i = 0; $i < count(array_keys((array)$ins)); $i++) {
            $res[0] = $res[0] . array_keys((array)$ins)[$i] . ',';
            $res[1][] = $ins[array_keys((array)$ins)[$i]];
            $q = $q . '?,';
        }
        $res[0] = rtrim($res[0], ',');
        $res[0] = $res[0] . ') VALUES (' . rtrim($q, ',') . ');';

        return $res;
    }

    public function genUpdateQuery($data, $t, $id)
    {
        $data = (array) $data;
        $keys = array_keys($data);
        $values = array_values($data);
        $res = array('UPDATE ' . $t . ' SET ', array());
        for ($i = 0; $i < count($keys); $i++) {
            if ($values[$i] == 'now()') {
                $res[0] = $res[0] . $keys[$i] . '=now(), ';
            } else {
                $res[0] = $res[0] . $keys[$i] . '=?, ';
                $res[1][] = $values[$i] ? $values[$i] : null;
            }
        }
        $res[0] = rtrim($res[0], ', ');
        $res[0] = $res[0] . ' WHERE Id = ' . $id;

        return $res;
    }

    public function stripAll($object)
    {
        foreach (array_keys((array)$object) as $key) {
            $object[$key] = htmlspecialchars(strip_tags($object[$key]));
        }
        return $object;
    }
}
