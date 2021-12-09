<?php
require_once __DIR__ . '/../utils/database.php';
class Script
{
    private $dataBase;
    private $table = 'Script';
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

    public function searchScripts($isAdmin, $userId, $searchString = '')
    {
        $query = "SELECT s.id, s.name, s.isFolder, s.parentFolderId FROM UserScript us JOIN Script s ON us.scriptId = s.id WHERE us.userId=$userId AND s.name LIKE '%$searchString%' ORDER BY s.isFolder DESC";
        if ($isAdmin) {
            $query = "SELECT s.id, s.name, s.isFolder, s.parentFolderId FROM Script s WHERE s.name LIKE '%$searchString%' ORDER BY s.isFolder DESC";
        }
        $stmt = $this->dataBase->db->query($query);
        $folders = [];
        while ($folder = $stmt->fetch()) {
            $folder['isFolder'] =  $folder['isFolder'] == '1';
            $folder['parentFolderId'] =  $folder['parentFolderId'] * 1;
            $folder['id'] =  $folder['id'] * 1;
            $folders[] = $folder;
        }
        return $folders;
    }

    public function getBlocks($scriptId)
    {
        $query = "SELECT id, name FROM Block WHERE scriptId=?";
        $query = $this->dataBase->db->prepare($query);
        $query->execute(array($scriptId));
        $blocks = [];
        while ($block = $query->fetch()) {
            $block['id'] =  $block['id'] * 1;
            $blocks[] = $block;
        }
        return $blocks;
    }
    public function getFolder($isAdmin, $userId, $folderId = null, $searchString = '')
    {
        $result = array();
        if ($folderId) {
            $query = "SELECT s.id, s.name FROM UserScript us JOIN Script s ON us.scriptId = s.id WHERE us.userId=$userId AND s.id=?";
            if ($isAdmin) {
                $query = "SELECT s.id, s.name FROM Script s WHERE s.id=?";
            }
            $query = $this->dataBase->db->prepare($query);
            $query->execute(array($folderId));
            $result = $query->fetch();
            if (!$result) {
                throw new Exception('Каталог не найден', 404);
            }
            $result['breadCrumbs'] = $this->getBreadCrumbs($folderId);
        }

        $result['scripts'] = $this->getFolderChildren($isAdmin, $userId, $folderId, $searchString);

        return $result;
    }

    private function getFolderChildren($isAdmin, $userId, $folderId = null, $searchString = '')
    {
        $query = "SELECT s.id, s.name, s.isFolder, s.parentFolderId, s.lastModifyDate, s.lastModifyUserId FROM UserScript us JOIN Script s ON us.scriptId = s.id WHERE us.userId=$userId AND parentFolderId IS ? AND name LIKE '%$searchString%' ORDER BY isFolder DESC";
        if ($isAdmin) {
            $query = "SELECT s.id, s.name, s.isFolder, s.parentFolderId, s.lastModifyDate, s.lastModifyUserId FROM Script s WHERE parentFolderId IS ? AND name LIKE '%$searchString%' ORDER BY isFolder DESC";
        }
        if ($folderId) {
            if ($isAdmin) {
                $query = "SELECT s.id, s.name, s.isFolder, s.parentFolderId, s.lastModifyDate, s.lastModifyUserId FROM Script s WHERE parentFolderId = ? AND name LIKE '%$searchString%' ORDER BY isFolder DESC";
            } else {
                $query = "SELECT s.id, s.name, s.isFolder, s.parentFolderId, s.lastModifyDate, s.lastModifyUserId FROM UserScript us JOIN Script s ON us.scriptId = s.id WHERE us.userId=$userId AND parentFolderId = ? AND name LIKE '%$searchString%' ORDER BY isFolder DESC";
            }
        }
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($folderId));
        $scripts = [];
        while ($script = $stmt->fetch()) {
            $script['id'] =  $script['id'] * 1;
            $script['isFolder'] =  $script['isFolder'] == '1';
            $script['parentFolderId'] = $script['parentFolderId'] ? $script['parentFolderId'] * 1 : null;
            $script['lastModifyDate'] = $script['lastModifyDate'] ? date("Y/m/d H:i:s", strtotime($script['lastModifyDate'])) : null;

            $scripts[] = $script;
        }
        return $scripts;
    }

    public function read($isAdmin, $userId, $scriptId, $block, $isOperator = false)
    {
        $query = "SELECT s.id, s.name, lastModifyDate, lastModifyUserId FROM UserScript us JOIN Script s ON s.id=us.scriptId WHERE us.userId=$userId AND s.id=$scriptId";
        if ($isAdmin) {
            $query = "SELECT s.id, s.name, lastModifyDate, lastModifyUserId FROM Script s WHERE s.id=$scriptId";
        }
        $script = $this->dataBase->db->query($query)->fetch();
        if (!$script) {
            throw new Exception('Скрипт не найден', 404);
        }
        $script['id'] =  $script['id'] * 1;
        $script['lastModifyDate'] = $script['lastModifyDate'] ? date("Y/m/d H:i:s", strtotime($script['lastModifyDate'])) : null;
        $script['blocks'] = $isOperator ? $this->readFirstBlock($scriptId) : $this->readBlocks($scriptId, $userId, $block);
        if ($isOperator) {
            $script['favoriteBlocks'] = $this->readFavoriteBlocks($scriptId, $userId);
        }
        $script['breadCrumbs'] = $this->getBreadCrumbs($script['id']);
        return $script;
    }

    public function isOpened($isAdmin, $userId, $scriptId)
    {
        $query = "SELECT s.id, s.name, lastModifyDate, lastModifyUserId FROM UserScript us JOIN Script s ON s.id=us.scriptId WHERE us.userId=$userId AND s.id=$scriptId";
        if ($isAdmin) {
            $query = "SELECT s.id, s.name, lastModifyDate, lastModifyUserId FROM Script s WHERE s.id=$scriptId";
        }
        $script = $this->dataBase->db->query($query)->fetch();

        return !!$script;
    }

    public function getBreadCrumbs($scriptId, $result = array())
    {
        $query = "SELECT id, name, parentFolderId FROM $this->table WHERE id='$scriptId'";
        $script = $this->dataBase->db->query($query)->fetch();
        $script['id'] =  $script['id'] * 1;
        array_unshift($result, $script);
        if (!$script['parentFolderId']) {
            return $result;
        }

        return $this->getBreadCrumbs($script['parentFolderId'], $result);
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

    public function createUserScript($userId, $scriptId)
    {
        $userScriptId = null;
        $query = "INSERT INTO UserScript (userId, scriptId) VALUES (?, ?)";
        $stmt = $this->dataBase->db->prepare($query);

        try {
            $stmt->execute([$userId, $scriptId]);
            $userScriptId = $this->dataBase->db->lastInsertId();
        } catch (Exception $e) {
            $query = "SELECT id FROM UserScript WHERE userId=? AND scriptId=?";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute([$userId, $scriptId]);
            $userScriptId = $stmt->fetch()['id'] * 1;
        }

        return $userScriptId;
    }

    public function getScriptVariables($scriptId)
    {
        $query = "SELECT * FROM ScriptParam WHERE scriptId=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute([$scriptId]);

        $params = [];
        while ($param = $stmt->fetch()) {
            $param['id'] = $param['id'] * 1;
            $params[] = $param;
        }
        return $params;
    }

    public function createScriptVariable($scriptId, $request)
    {
        $request['scriptId'] = $scriptId;
        $query = $this->dataBase->genInsertQuery($request, 'ScriptParam');
        $stmt = $this->dataBase->db->prepare($query[0]);
        try {
            $stmt->execute($query[1]);
        } catch (Exception $e) {
            throw new Exception('Такое название переменной уже существует в скрипте', 409);
        }


        return $this->dataBase->db->lastInsertId();
    }

    public function updateScriptVariable($request)
    {
        $id = $request['id'];
        unset($request['id']);

        $query = $this->dataBase->genUpdateQuery($request, 'ScriptParam', $id);
        $stmt = $this->dataBase->db->prepare($query[0]);

        try {
            $stmt->execute($query[1]);
        } catch (Exception $e) {
            throw new Exception('Такое название переменной уже существует в скрипте', 409);
        }

        return true;
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

    public function deleteScriptVariable($id)
    {
        $query = "delete from ScriptParam where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    public function delete($scriptId)
    {
        $query = "delete from Script where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($scriptId));
        return true;
    }

    private function readBlocks($scriptId, $userId, Block $blockModel)
    {
        $query = "SELECT b.id, b.name, b.isGroup, b.description, b.lastModifyDate, b.lastModifyUserId, b.blockIndex, (SELECT count(*) FROM UserScriptFavorite usf JOIN UserScript us ON us.id=usf.userScriptId WHERE us.userId=? AND usf.blockId=b.id) as isFavorite FROM Block b WHERE b.scriptId=? ORDER BY b.blockIndex";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($userId, $scriptId));
        $blocks = [];
        while ($block = $stmt->fetch()) {
            $block['isGroup'] = $block['isGroup'] == '1';
            $block['isFavorite'] = $block['isFavorite'] == '1';
            $block['blockIndex'] = $block['blockIndex'] * 1;
            $block['lastModifyDate'] = $block['lastModifyDate'] ? date("Y/m/d H:i:s", strtotime($block['lastModifyDate'])) : null;
            $block['id'] = $block['id'] * 1;
            $block['incommingTransitions'] = $blockModel->getTransitions($block['id']);
            $block['outgoingTransitions'] = $blockModel->getTransitions($block['id'], false);
            $blocks[] = $block;
        }
        return $blocks;
    }

    private function readFavoriteBlocks($scriptId, $userId)
    {
        $query = "SELECT b.id, b.name FROM UserScriptFavorite usf JOIN UserScript us ON us.id=usf.userScriptId JOIN Block b ON b.id=usf.blockId WHERE us.userId=? AND us.scriptId=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($userId, $scriptId));
        $blocks = [];
        while ($block = $stmt->fetch()) {
            $block['id'] = $block['id'] * 1;
            $blocks[] = $block;
        }
        return $blocks;
    }

    private function readFirstBlock($scriptId)
    {
        $query = "SELECT b.id, b.name, b.description, b.lastModifyDate, b.lastModifyUserId FROM Block b WHERE b.scriptId=? AND b.blockIndex=0";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($scriptId));
        $block = $stmt->fetch();

        if (!$block) {
            return [];
        }

        $blockModel = new Block($this->dataBase);

        $block['lastModifyDate'] = $block['lastModifyDate'] ? date("Y/m/d H:i:s", strtotime($block['lastModifyDate'])) : null;
        $block['id'] = $block['id'] * 1;
        $block['incommingTransitions'] = $blockModel->getTransitions($block['id']);
        $block['outgoingTransitions'] = $blockModel->getTransitions($block['id'], false);
        return [$block];
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
