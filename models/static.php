<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
class Category
{
    private $dataBase;
    private $table = 'Category';
    private $fileUploader;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
    }

    public function read($id, $withProducts = true)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $category = $stmt->fetch();
        if (!$category) {
            throw new Exception("Category not found", 404);
        }
        if ($withProducts) {
            $category['products'] = $this->readProducts($id);
        }


        return $category;
    }

    public function updateMain($request)
    {
        return true;
    }

    public function createVideo($request)
    {
        return true;
    }

    public function updateVideo($id, $request)
    {
        return true;
    }

    public function deleteVideo($id)
    {
        return true;
    }

    public function createComment($id, $request)
    {
        return true;
    }

    public function updateComment($id, $request)
    {
        return true;
    }

    public function deleteComment($id)
    {
        return true;
    }

    public function createClient($request, $file)
    {
        return true;
    }

    public function updateClient($id, $request, $file)
    {
        return true;
    }

    public function deleteClient($id)
    {
        return true;
    }
}
