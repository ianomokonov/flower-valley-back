<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../models/script.php';
require_once __DIR__ . '/../utils/filesUpload.php';
class Product
{
    private $dataBase;
    private $table = 'Product';
    private $fileUploader;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
    }

    public function read($id)
    {
        $query = "SELECT * FROM Block b WHERE b.id=? ";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $product['id'] = $product['id'] * 1;
        $product['price'] = $product['price'] * 1;
        $product['nds'] = $product['nds'] * 1;
        $product['ndsMode'] = $product['ndsMode'] * 1;

        return $product;
    }

    public function create($request, $photos)
    {
        $categoryIds = $request['categoryIds'];
        unset($request['categoryIds']);
        $request = $this->dataBase->stripAll((array)$request);

        $query = $this->dataBase->genInsertQuery($request, $this->table);

        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        $productId = $this->dataBase->db->lastInsertId();

        $this->setPhotos($productId, $photos);
        $this->setCategories($productId, $categoryIds);


        return $productId;
    }

    public function update($productId, $request, $photos)
    {
        $categoryIds = $request['categoryIds'];
        unset($request['categoryIds']);
        $request = $this->dataBase->stripAll((array)$request);

        $query = $this->dataBase->genUpdateQuery($request, $this->table, $productId);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        $productId = $this->dataBase->db->lastInsertId();

        $this->setPhotos($productId, $photos);
        $this->setCategories($productId, $categoryIds);

        return true;
    }

    public function delete($productId)
    {
        $this->unsetPhotos($productId);
        $this->unsetCategories($productId);
        $query = "delete from " . $this->table . " where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($productId));
        return true;
    }

    private function setPhotos($productId, $photos)
    {
        $this->unsetPhotos($productId);

        $res = [];
        foreach ($photos as $key => $value) {
            $imagePath = $this->dataBase->baseUrl . $this->fileUploader->upload($value, 'Images', uniqid());

            $values = array("productId" => $productId, "src" =>  $imagePath);
            $query = $this->dataBase->genInsertQuery($values, "ProductImage");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
            $res[] = $imagePath;
        }

        return $res;
    }

    private function setCategories($productId, $categoryIds)
    {
        $this->unsetCategories($productId);
        foreach ($categoryIds as $key => $value) {
            $values = array("productId" => $productId, "categoryId" =>  $value);
            $query = $this->dataBase->genInsertQuery($values, "ProductCategory");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }
    }

    private function unsetCategories($productId)
    {
        $stmt = $this->dataBase->db->prepare("delete from ProductCategory where productId=?");
        $stmt->execute(array($productId));
    }

    private function unsetPhotos($productId)
    {
        $stmt = $this->dataBase->db->prepare("select src from ProductImage where productId=?");
        $stmt->execute(array($productId));
        while ($url = $stmt->fetch()) {
            $this->fileUploader->removeFile($url['src'], $this->dataBase->baseUrl);
        }

        $stmt = $this->dataBase->db->prepare("delete from ProductImage where productId=?");
        $stmt->execute(array($productId));

        return true;
    }
}
