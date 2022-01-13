<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/product.php';
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
        $category['parentId'] = $category['parentId'] * 1;
        $category['id'] = $category['id'] * 1;
        if (!$category) {
            throw new Exception("Category not found", 404);
        }
        if ($withProducts) {
            $category['products'] = $this->readProducts($id);
        }


        return $category;
    }

    public function readFirst($productId)
    {
        $query = "SELECT c.id, c.name FROM ProductCategory pc JOIN Category c ON pc.categoryId = c.id WHERE pc.productId = ? LIMIT 1";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($productId));
        $category = $stmt->fetch();
        $category['id'] = $category['id'] * 1;


        return $category;
    }

    public function readSimle($id)
    {
        $query = "SELECT c.id, c.name FROM Category c WHERE c.id = ? LIMIT 1";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $category = $stmt->fetch();
        $category['id'] = $category['id'] * 1;


        return $category;
    }

    private function readProducts($id)
    {
        $query = "SELECT p.id, p.name, p.price, p.volume, p.coefficient, p.pack, p.description, p.boxId FROM Product p JOIN ProductCategory pc ON p.id = pc.productId WHERE pc.categoryId=? ORDER BY p.id";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $products = $stmt->fetchAll();



        if (!$products || count($products) == 0) {
            return $products;
        }

        $productModel = new Product($this->dataBase);
        $result = [];
        foreach ($products as $key => $product) {
            $product['price'] = $product['price'] * 1;
            $product['boxId'] = $product['boxId'] * 1;
            $product['coefficient'] = $product['coefficient'] * 1;
            $product['photos'] = $productModel->getPhotos($product['id']);
            $result[] = $product;
        }
        return $result;
    }

    public function create($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'CategoryImages', uniqid());
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function update($categoryId, $request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        if ($file) {
            $this->removeCategoryImg($categoryId);
            $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'CategoryImages', uniqid());
        }
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $categoryId);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function delete($categoryId)
    {
        $this->removeCategoryImg($categoryId);
        $query = "delete from " . $this->table . " where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($categoryId));
        return true;
    }

    public function getList()
    {
        $query = $this->dataBase->genSelectQuery($this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);
        $result = [];
        while ($category = $stmt->fetch()) {
            $category['parentId'] = $category['parentId'] * 1;
            $category['id'] = $category['id'] * 1;
            $result[] = $category;
        }
        return $result;
    }

    private function removeCategoryImg($id)
    {
        $category = $this->read($id);
        if (!$category['img']) {
            return;
        }

        $this->fileUploader->removeFile($category['img'], $this->dataBase->baseUrl);
    }
}
