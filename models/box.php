<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
class Box
{
    private $dataBase;
    private $table = 'Box';

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    public function read($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $box = $stmt->fetch();
        $box['id'] = $box['id'] * 1;
        $box['volume'] = $box['volume'] * 1;
        $box['price'] = $box['price'] * 1;

        return $box;
    }

    public function getList()
    {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->dataBase->db->query($query);
        $result = [];

        while ($box = $stmt->fetch()) {
            $box['id'] = $box['id'] * 1;
            $box['volume'] = $box['volume'] * 1;
            $box['price'] = $box['price'] * 1;
            $result[] = $box;
        }
        return $result;
    }

    public function update($id, $request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function create($request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function delete($id)
    {
        $query = "delete from " . $this->table . " where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }
}
