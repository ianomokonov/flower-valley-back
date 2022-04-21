<?php
//класс базы данных
class DataBase
{
    private $dbname = "nomokoiw_flower3";
    private $login = "nomokoiw_flower3";
    private $password = "x*bub3Zk";
    public $db;
    public static $host = 'http://stand3.progoff.ru/';
    public static $baseUrl = 'http://stand3.progoff.ru/back';
    public function __construct()
    {
        $this->db = new PDO("mysql:host=localhost;dbname=" . $this->dbname . ";charset=UTF8", $this->login, $this->password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function genSelectQuery($table, $whereStmts = [])
    {
        $whereStmts = (array) $whereStmts;
        $res = array('SELECT * FROM ' . $table, array());

        if (count($whereStmts) > 0) {
            $res[0] = $res[0] . ' WHERE';

            foreach ($whereStmts as $key => $value) {
                if ($value == null) {
                    $res[0] = $res[0] . " $key IS ? AND";
                } else if (strpos($value, 'LIKE') || strpos($value, 'LIKE') == 0) {
                    $res[0] = $res[0] . " $key $value AND";
                } else {
                    $res[0] = $res[0] . " $key=?,";
                    $res[1][] = $value;
                }
            }
            $res[0] = rtrim($res[0], ' AND');
        }

        $res[0] = $res[0] . ';';

        return $res;
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

    public function genUpdateQuery($data, $t, $id, $idField = "id")
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
                if($values[$i] == false){
                    $res[1][] = false;
                } else {
                    $res[1][] = $values[$i] ? $values[$i] :  null;
                }
                
            }
        }
        $res[0] = rtrim($res[0], ', ');
        $res[0] = $res[0] . ' WHERE ' . $idField . ' = ?';
        $res[1][] = $id;

        return $res;
    }

    public function stripAll($object, $unsetId = false)
    {
        if ($unsetId && isset($object['id'])) {
            unset($object['id']);
        }
        foreach (array_keys((array)$object) as $key) {
            if ($object[$key]) {
                $object[$key] = strip_tags($object[$key], ['span', 'strong', 'p', 'i', 'b', 'div', 'a', 'u', 'em', 'ol', 'li', 'ul']);
            }
        }
        return $object;
    }

    public function strip($param)
    {
        return strip_tags($param);
    }
}
