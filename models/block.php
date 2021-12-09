<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../models/script.php';
class Block
{
    private $dataBase;
    private $table = 'Block';
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
        $block['incommingTransitions'] = $this->getTransitions($block['id']);
        $block['outgoingTransitions'] = $this->getTransitions($block['id'], false);
        return $block;
    }

    public function create($userId, $request)
    {
        $scriptModel = new Script($this->dataBase);
        $request = $this->dataBase->stripAll((array)$request);
        $request['lastModifyUserId'] = $userId;
        $request['blockIndex'] = count($scriptModel->getBlocks($request['scriptId']));
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

    public function createTransition($userId, $blockId, $request)
    {
        if ($request['block']) {
            $request['nextBlockId'] = $this->create($userId, $request['block']);
            unset($request['block']);
        }
        $request['lastModifyUserId'] = $userId;
        $request['blockId'] = $blockId;
        $request = $this->dataBase->stripAll((array)$request);
        $query = $this->dataBase->genInsertQuery(
            $request,
            'Transition'
        );

        // подготовка запроса
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0] != null) {
            $stmt->execute($query[1]);
        }
        return $this->dataBase->db->lastInsertId();
    }

    public function updateTransition($userId, $transitionId, $request)
    {
        if ($request['block']) {
            $request['nextBlockId'] = $this->create($userId, $request['block']);
            unset($request['block']);
        }
        $request = $this->dataBase->stripAll((array)$request);
        $request['lastModifyUserId'] = $userId;
        $request['lastModifyDate'] = 'now()';
        $query = $this->dataBase->genUpdateQuery(
            $request,
            'Transition',
            $transitionId
        );

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);
        return true;
    }

    public function getTransitions($blockId, $incomming = true)
    {
        $query = "SELECT * FROM Transition WHERE blockId = ?";
        if ($incomming) {
            $query = "SELECT * FROM Transition WHERE nextBlockId = ?";
        }
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($blockId));
        $transitions = [];
        while ($transition = $stmt->fetch()) {
            $transition['id'] =  $transition['id'] * 1;
            $transition['blockId'] =  $transition['blockId'] * 1;
            $transition['nextBlockId'] =  $transition['nextBlockId'] * 1;
            $transition['status'] = $transition['status'] ? $transition['status'] * 1 : null;
            $transition['lastModifyDate'] = $transition['lastModifyDate'] ? date("Y/m/d H:00:00", strtotime($transition['lastModifyDate'])) : null;

            $transitions[] = $transition;
        }
        return $transitions;
    }

    public function delete($blockId)
    {
        $query = "delete from Block where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($blockId));
        return true;
    }

    public function deleteTransition($transitionId)
    {
        $query = "delete from Transition where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($transitionId));
        return true;
    }

    public function markBlock($blockId, $userId, $request)
    {

        $scriptModel = new Script($this->dataBase);
        $userScriptId = $scriptModel->createUserScript($userId, $request['scriptId']);

        if ($request['isFavorite']) {
            $query = "insert into UserScriptFavorite (userScriptId, blockId) VALUES (?, ?)";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute(array($userScriptId, $blockId));
            return true;
        }
        $query = "delete from UserScriptFavorite where userScriptId=? AND blockId=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($userScriptId, $blockId));
        return true;
    }
}
