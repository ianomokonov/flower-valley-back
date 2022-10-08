<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/product.php';
require_once __DIR__ . '/sale.php';
require_once __DIR__ . '/../utils/filesUpload.php';

abstract class CategoryType
{
    const Tulip = 1;
    const Seedling = 2;
}
class Category
{
    private $dataBase;
    private $table = 'Category';
    private $fileUploader;
    private $sale;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
        $this->sale = new Sale($this->dataBase);
    }

    public function read($id, $withProducts = true)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $category = $stmt->fetch();
        $category['parentId'] = $category['parentId'] * 1;
        $category['id'] = $category['id'] * 1;
        $category['isSeedling'] = $category['categoryType'] == CategoryType::Seedling;
        $category['isTulip'] = $category['categoryType'] == CategoryType::Tulip;
        unset($category['categoryType']);
        if ($category['isTulip']) {
            $category['steps'] = $this->readSteps($category['id']);
        }

        if (!$category) {
            throw new Exception("Category not found", 404);
        }
        if ($withProducts) {
            $category['products'] = $this->readProducts($id);
        }
        $category['sale'] = $this->sale->getCategorySale($category['id']);

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
        $query = "SELECT c.id, c.name, c.categoryOrder, c.parentId FROM Category c WHERE c.id = ? LIMIT 1";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $category = $stmt->fetch();
        $category['id'] = $category['id'] * 1;
        $category['categoryOrder'] = $category['categoryOrder'] * 1;


        return $category;
    }

    public function readSimpleByName($name)
    {
        $query = "SELECT c.id, c.name FROM Category c WHERE LOWER(c.name) = ? LIMIT 1";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($name));

        return $stmt->fetch();
    }

    public function sortCategories($categories)
    {
        foreach ($categories as $category) {
            $query = "update Category set categoryOrder=? where id=?";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute(array($category['categoryOrder'], $category['id']));
        }
        return true;
    }

    private function readProducts($id)
    {
        $query = "SELECT p.id, p.name, p.price, p.volume, p.coefficient, p.pack, p.description, p.boxId, pc.id as productCategoryId, pc.productOrder FROM Product p JOIN ProductCategory pc ON p.id = pc.productId WHERE pc.categoryId=? ORDER BY pc.productOrder";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $products = $stmt->fetchAll();



        if (!$products || count($products) == 0) {
            return $products;
        }

        $productModel = new Product($this->dataBase);
        $result = [];
        foreach ($products as $product) {
            $product['price'] = $product['price'] * 1;
            $product['prices'] = $productModel->getPrice($product['id']);
            $product['boxId'] = $product['boxId'] * 1;
            $product['productOrder'] = $product['productOrder'] * 1;
            $product['productCategoryId'] = $product['productCategoryId'] * 1;
            $product['coefficient'] = $product['coefficient'] * 1;
            $product['sale'] = $this->sale->getProductSale($product['id'], $product['price']);
            $product['photos'] = $productModel->getPhotos($product['id']);
            $product['categories'] = $this->getProductCategories($product['id']);
            $result[] = $product;
        }
        return $result;
    }

    public function readProductsSimple($id)
    {
        $query = "SELECT * FROM ProductCategory WHERE categoryId=? ORDER BY productOrder";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $products = $stmt->fetchAll();



        if (!$products || count($products) == 0) {
            return [];
        }
        return $products;
    }

    public function setSteps($categoryId, $steps)
    {
        $categoryId = $this->readParentCategory($categoryId)['id'];
        $this->unsetItems($categoryId, "CategoryStep");
        foreach ($steps as $value) {
            $value['categoryId'] = $categoryId;
            $query = $this->dataBase->genInsertQuery($value, "CategoryStep");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }
    }

    private function unsetItems($categoryId, $table)
    {
        $stmt = $this->dataBase->db->prepare("delete from $table where categoryId=?");
        $stmt->execute(array($categoryId));
    }

    public function readSteps($id)
    {
        $query = "SELECT id, countFrom FROM CategoryStep WHERE categoryId = ? ORDER BY countFrom";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($this->readParentCategory($id)['id']));
        $result = [];
        while ($step = $stmt->fetch()) {

            $step['id'] = $step['id'] * 1;
            $step['countFrom'] = $step['countFrom'] * 1;
            $result[] = $step;
        }

        return $result;
    }

    public function readParentCategory($id)
    {
        $query = "SELECT c.id, c.parentId, c.categoryType, c.name FROM Category c WHERE c.id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));

        $category = $stmt->fetch();

        if (!$category['parentId']) {
            $category['id'] = $category['id'] * 1;
            $category['parentId'] = $category['parentId'] * 1;
            $category['isTulip'] = $category['categoryType'] == CategoryType::Tulip;
            $category['isSeedling'] = $category['categoryType'] == CategoryType::Seedling;
            return $category;
        }

        return $this->readParentCategory($category['parentId']);
    }

    public function create($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'CategoryImages', uniqid());
        if (isset($request['isBlocked'])) {
            $request['isBlocked'] = $request['isBlocked'] == 'true';
        }
        if (isset($request['hasNoDiscount'])) {
            $request['hasNoDiscount'] = $request['hasNoDiscount'] == 'true';
        }
        $request['categoryOrder'] = count($this->getList(false));
        if (isset($request['isSeedling'])) {
            $request['categoryType'] = $request['isSeedling'] == 'true' ? CategoryType::Seedling : null;
            unset($request['isSeedling']);
        }
        if (isset($request['isTulip'])) {
            $request['categoryType'] = $request['isTulip'] == 'true' ? CategoryType::Tulip : null;
            unset($request['isTulip']);
        }
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

        if (isset($request['isBlocked'])) {
            $request['isBlocked'] = $request['isBlocked'] == 'true';
        }
        if (isset($request['hasNoDiscount'])) {
            $request['hasNoDiscount'] = $request['hasNoDiscount'] == 'true';
        }
        if (isset($request['isSeedling'])) {
            $request['categoryType'] = $request['isSeedling'] == 'true' ? CategoryType::Seedling : null;
            unset($request['isSeedling']);
        }
        if (isset($request['isTulip'])) {
            $request['categoryType'] = $request['isTulip'] == 'true' ? CategoryType::Tulip : null;
            unset($request['isTulip']);
        }
        if ($file) {
            $this->removeCategoryImg($categoryId);
            $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'CategoryImages', uniqid());
        }
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $categoryId);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function delete($categoryId)
    {
        if ($this->isSpecial($categoryId)) {
            throw new Exception("Невозможно удалить базовую категорию", 409);
        }
        $this->removeCategoryImg($categoryId);
        $query = "delete from " . $this->table . " where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($categoryId));
        return true;
    }

    public function getList($withSale = true)
    {
        $query = $this->dataBase->genSelectQuery($this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);
        $result = [];
        while ($category = $stmt->fetch()) {
            $category['isTulip'] = $category['categoryType'] == CategoryType::Tulip;
            $category['isSeedling'] = $category['categoryType'] == CategoryType::Seedling;
            unset($category['categoryType']);
            if ($withSale) {
                $category['sale'] = $this->sale->getCategorySale($category['id']);
            }
            $result[] = $category;
        }
        return $result;
    }



    public function getProductCategories($productId)
    {
        $res = [];
        $stmt = $this->dataBase->db->prepare("select c.id, c.name, c.parentId, c.img from ProductCategory pc join Category c on c.id = pc.categoryId where productId=?");
        $stmt->execute(array($productId));
        while ($category = $stmt->fetch()) {
            $parent = $this->readParentCategory($category['id']);
            $category['isTulip'] = $parent['isTulip'];
            $category['isSeedling'] = $parent['isSeedling'];
            if ($category['isTulip']) {
                $category['steps'] = $this->readSteps($parent['id']);
            }
            $res[] = $category;
        }

        return $res;
    }

    private function removeCategoryImg($id)
    {
        $category = $this->read($id);
        if (!$category['img']) {
            return;
        }

        $this->fileUploader->removeFile($category['img'], DataBase::$baseUrl);
    }

    private function isSpecial($id)
    {
        $query = "SELECT * FROM Category WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return $stmt->fetch()['categoryType'] == CategoryType::Tulip;
    }
}
