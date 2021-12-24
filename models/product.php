<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../models/script.php';
class Product
{
    private $dataBase;
    private $table = 'Product';
    // private $baseUrl = 'http://localhost:4200/back';
    private $baseUrl = 'http://stand1.progoff.ru/back';

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    public function read($blockId)
    {
        $query = "SELECT b.id, b.name, b.description, b.lastModifyDate, b.lastModifyUserId FROM Block b WHERE b.id=? ";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($blockId));
        $block = $stmt->fetch();

        if (!$block) {
            return null;
        }

        $block['lastModifyDate'] = $block['lastModifyDate'] ? date("Y/m/d H:i:s", strtotime($block['lastModifyDate'])) : null;
        $block['id'] = $block['id'] * 1;
        return $block;
    }

    public function create($userId, $request)
    {
        return $this->dataBase->db->lastInsertId();
    }

    public function update($userId, $blockId, $request)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['lastModifyUserId'] = $userId;
        $request['lastModifyDate'] = 'now()';
        $query = $this->dataBase->genUpdateQuery(
            $request,
            $this->table,
            $blockId
        );

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);
        return true;
    }

    public function delete($blockId)
    {
        $query = "delete from Block where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($blockId));
        return true;
    }
}
