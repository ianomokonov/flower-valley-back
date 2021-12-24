<?php
require_once __DIR__ . '/../utils/database.php';
class Category
{
    private $dataBase;
    private $table = 'Category';
    // private $baseUrl = 'http://localhost:4200/back';
    private $baseUrl = 'http://stand1.progoff.ru/back';

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }
    public function getFolders($userId, $isAdmin)
    {
        $query = "SELECT s.id, s.name FROM UserScript us JOIN Script s ON us.scriptId = s.id WHERE us.userId=$userId AND s.isFolder=1";
        if ($isAdmin) {
            $query = "SELECT s.id, s.name FROM Script s WHERE s.isFolder=1";
        }
        $stmt = $this->dataBase->db->query($query);
        $folders = [];
        while ($folder = $stmt->fetch()) {
            $folder['id'] =  $folder['id'] * 1;
            $folders[] = $folder;
        }
        return $folders;
    }

    public function create($userId, $request)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['lastModifyUserId'] = $userId;
        $query = $this->dataBase->genInsertQuery(
            $request,
            $this->table
        );

        // подготовка запроса
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0] != null) {
            $stmt->execute($query[1]);
        }
        return $this->dataBase->db->lastInsertId();
    }

    public function update($userId, $scriptId, $request)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['lastModifyUserId'] = $userId;
        $request['lastModifyDate'] = 'now()';
        $query = $this->dataBase->genUpdateQuery(
            $request,
            $this->table,
            $scriptId
        );

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);
        return true;
    }

    public function delete($scriptId)
    {
        $query = "delete from Script where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($scriptId));
        return true;
    }

    public function sortBlocks($blocks)
    {
        foreach ($blocks as $block) {
            $query = "update Block set blockIndex=? where id=?";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute(array($block['index'], $block['id']));
        }
        return true;
    }
}
