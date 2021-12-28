<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/product.php';
class Category
{
    private $dataBase;
    private $table = 'Category';

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    public function read($id)
    {
        $query = $this->dataBase->genSelectQuery($this->table, array("id" => $id));
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);
        $category = $stmt->fetch();

        $category['products'] = $this->readProducts($id);

        return $category;
    }

    private function readProducts($id)
    {
        $query = "SELECT * FROM Product p JOIN ProductCategory pc ON p.id = pc.productId WHERE pc.categoryId=? ";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $products = $stmt->fetchAll();

        if (!count($products) > 0) {
            return $products;
        }

        $productModel = new Product($this->dataBase);

        foreach ($products as $key => $product) {
            $product['id'] = $product['id'] * 1;
            $product['price'] = $product['price'] * 1;
            $product['nds'] = $product['nds'] * 1;
            $product['ndsMode'] = $product['ndsMode'] * 1;
            $product['photos'] = $productModel->getPhotos($product['id']);
        }
        return $products;
    }

    public function create($request)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }
        $request['id'] = $this->dataBase->db->lastInsertId();


        return $request['id'];
    }

    public function update($categoryId, $request)
    {
        unset($request['id']);
        $request = $this->dataBase->stripAll((array)$request);

        $query = $this->dataBase->genUpdateQuery($request, $this->table, $categoryId);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function delete($categoryId)
    {
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
        return $stmt->fetchAll();
    }
}
