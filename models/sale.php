<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
require_once __DIR__ . '/category.php';
require_once __DIR__ . '/product.php';
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
        $category = new Category($this->dataBase);
        $product = new Product($this->dataBase);
        $query = "SELECT * FROM Sale ORDER BY `order`";
        $stmt = $this->dataBase->db->query($query);

        $result = [];
        while ($sale = $stmt->fetch()) {
            $sale['id'] = $sale['id'] * 1;
            $sale['order'] = $sale['order'] * 1;
            $sale['discount'] = $sale['discount'] * 1;
            if ($sale['productId']) {
                $sale['category'] = $category->readFirst($sale['productId']);
                $sale['currentPrice'] = $product->getCurrentPrice($sale['productId']);
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

    public function getSaleById($id)
    {
        $query = "SELECT * FROM Sale WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $sale = $stmt->fetch();
        if (!$sale) {
            return null;
        }

        return $sale;
    }

    public function createSale($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['discount'] = $request['discount'] * 1;
        $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'SaleImages', uniqid());
        if (isset($request['isActive'])) {
            $request['isActive'] = $request['isActive'] == 'true';
        }
        if (isset($request['isVisible'])) {
            $request['isVisible'] = $request['isVisible'] == 'true';
        }
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
        if (isset($request['isActive'])) {
            $request['isActive'] = $request['isActive'] == 'true';
        }
        if (isset($request['isVisible'])) {
            $request['isVisible'] = $request['isVisible'] == 'true';
        }
        if ($file) {
            $this->removeImg('Sale', $id);
            $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'SaleImages', uniqid());
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

    public function sortSales($sales)
    {
        foreach ($sales as $sale) {
            $query = "update Sale set order=? where id=?";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute(array($sale['order'], $sale['id']));
        }
        return true;
    }

    public function  getCategorySale($categoryId, $sales = [], $categoryModel = null)
    {
        if(!$categoryModel){
            $categoryModel = new Category($this->dataBase);
        }
        $category = $categoryModel->readSimle($categoryId);
        $sale = $this->getSale($categoryId, true);

        if ($sale) {
            $sales[] = $sale['discount'];
        }

        if ($category['parentId']) {
            return $this->getCategorySale($category['parentId'], $sales, $categoryModel);
        }

        return count($sales) ? max($sales) : null;
    }

    public function  getProductSale($productId, $productPrice, $categoryModel = null)
    {
        if(!$categoryModel){
            $categoryModel = new Category($this->dataBase);
        }
        $productSale = $this->getSale($productId, false);
        if ($productSale) {
            $productSale = min([$productPrice, $productSale['discount']]);
        } else {
            $productSale = $productPrice;
        }
        $categories = $categoryModel->getProductCategories($productId);
        $categorySales = [];

        foreach ($categories as $category) {
            $sale = $this->getCategorySale($category['id']);

            if ($sale) {
                $categorySales[] = $sale;
            }
        }

        if (count($categorySales) == 0) {
            return $productSale == $productPrice ? null : $productSale;
        }

        return min([$productSale, max($categorySales) / 100 * $productPrice]);
    }

    private function removeImg($table, $id)
    {
        $object = $this->readObj($table, $id);
        if (!$object['img']) {
            return;
        }

        $this->fileUploader->removeFile($object['img'], DataBase::$baseUrl);
    }

    private function readObj($table, $id)
    {
        $query = "SELECT * FROM " . $table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return $stmt->fetch();
    }

    private function getSale($id, $isCategory)
    {
        $query = "SELECT * FROM Sale ";
        if ($isCategory) {
            $query = $query . "WHERE categoryId = ?";
        } else {
            $query = $query . "WHERE productId = ?";
        }
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $sale = $stmt->fetch();
        if (!$sale) {
            return null;
        }

        return $sale;
    }
}
