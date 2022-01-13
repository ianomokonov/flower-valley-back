<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
require_once __DIR__ . '/category.php';
class Sale
{
    private $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
    }

    public function getList()
    {
        $query = "SELECT * FROM Sale";
        $stmt = $this->dataBase->db->query($query);

        $result = [];
        $category = new Category($this->dataBase);
        while ($sale = $stmt->fetch()) {
            $sale['id'] = $sale['id'] * 1;
            $sale['discount'] = $sale['discount'] * 1;
            if ($sale['productId']) {
                $sale['productId'] = $sale['productId'] * 1;
                $sale['category'] = $category->readFirst($sale['productId']);
                $result[] = $sale;
                continue;
            }
            if ($sale['categoryId']) {
                $sale['category'] = $category->readSimle($sale['categoryId']);
            }
            $result[] = $sale;
        }

        return $result;
    }

    public function createSale($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['discount'] = $request['discount'] * 1;
        $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'SaleImages', uniqid());
        $query = $this->dataBase->genInsertQuery($request, 'Sale');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function updateSale($id, $request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $request['discount'] = $request['discount'] * 1;
        if ($file) {
            $this->removeImg('Sale', $id);
            $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'SaleImages', uniqid());
        }
        $query = $this->dataBase->genUpdateQuery($request, 'Sale', $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteSale($id)
    {
        $this->removeImg('Sale', $id);
        $query = "delete from Sale where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    private function removeImg($table, $id)
    {
        $object = $this->readObj($table, $id);
        if (!$object['img']) {
            return;
        }

        $this->fileUploader->removeFile($object['img'], $this->dataBase->baseUrl);
    }

    private function readObj($table, $id)
    {
        $query = "SELECT * FROM " . $table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return $stmt->fetch();
    }
}
