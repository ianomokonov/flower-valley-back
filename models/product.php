<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
require_once __DIR__ . '/../utils/mailer.php';
require_once __DIR__ . '/category.php';
require_once __DIR__ . '/sale.php';
class Product
{
    private $dataBase;
    private $table = 'Product';
    private $fileUploader;
    private $sale;
    private $category;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
        $this->category = new Category($this->dataBase);
        $this->sale = new Sale($dataBase);
    }

    public function search($str)
    {
        $str = htmlspecialchars(strip_tags($str));
        $query = "SELECT DISTINCT p.id, p.name, p.price, p.boxId, p.coefficient FROM Product p WHERE p.name LIKE '%$str%'";

        $stmt = $this->dataBase->db->query($query);

        $result = [];

        while ($p = $stmt->fetch()) {
            $c = $this->category->readParentCategory($this->category->readFirst($p['id'])['id']);
            $p['categoryId'] = $c['id'];
            $p['categoryName'] = $c['name'];
            $p['prices'] = $this->getPrice($p['id']);
            $p['sale'] = $this->sale->getProductSale($p['id'], $p['price']);
            $result[] = $p;
        }

        return $result;
    }

    public function getPopular($raw = false)
    {
        $query = "SELECT p.id, p.name, p.price, p.boxId, p.coefficient, p.popularOrder FROM Product p WHERE p.isPopular ORDER BY p.popularOrder";

        $stmt = $this->dataBase->db->query($query);

        if ($raw) {
            return $stmt->fetchAll();
        }

        $result = [];

        while ($p = $stmt->fetch()) {
            $p['prices'] = $this->getPrice($p['id']);
            $p['photos'] = $this->getPhotos($p['id'], true);
            $c = $this->category->readFirst($p['id']);
            $p['categoryId'] = $c['id'];
            $p['categoryName'] = $c['name'];
            $p['sale'] = $this->sale->getProductSale($p['id'], $p['price']);
            $result[] = $p;
        }

        return $result;
    }

    public function read($id)
    {
        $query = "SELECT * FROM Product p WHERE p.id=? ";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $product['price'] = $product['price'] * 1;
        $product['prices'] = $this->getPrice($product['id']);
        $product['nds'] = $product['nds'] * 1;
        $product['ndsMode'] = $product['ndsMode'] * 1;
        if ($product['boxId']) {
            $product['boxId'] = $product['boxId'] * 1;
        }
        $product['coefficient'] = $product['coefficient'] * 1;
        $product['isPopular'] = $product['isPopular'] == '1';
        $product['photos'] = $this->getPhotos($id);
        $product['categories'] = $this->category->getProductCategories($id);
        $product['sale'] = $this->sale->getProductSale($product['id'], $product['price']);

        return $product;
    }

    public function create($request, $photos)
    {
        $categoryIds = $request['categoryIds'];
        $prices = null;
        if (isset($request['prices'])) {
            $prices = $request['prices'];
        }
        unset($request['categoryIds']);
        unset($request['prices']);
        $request = $this->dataBase->stripAll((array)$request);
        if (isset($request['price'])) {
            $request['price'] = $request['price'] * 1;
        }
        if (isset($request['nds'])) {
            $request['nds'] = $request['nds'] * 1;
        }
        if (isset($request['ndsMode'])) {
            $request['ndsMode'] = $request['ndsMode'] * 1;
        }
        if (isset($request['isPopular'])) {
            $request['isPopular'] = $request['isPopular'] == 'true';

            if ($request['isPopular'] && (!isset($request['popularOrder']) || $request['popularOrder'] == null)) {
                $request['popularOrder'] = count($this->getPopular(true));
            }
        }

        if (isset($request['isPopular']) && !$request['isPopular']) {
            $request['popularOrder'] = null;
        }
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }
        $this->setPhotos($request['id'], [], $photos);
        $this->setCategories($request['id'], $categoryIds);
        if ($prices) {
            $this->setPrices($request['id'], $prices);
        }


        return $request['id'];
    }

    public function update($productId, $request, $photos)
    {
        unset($request['id']);
        $deleteIds = [];
        if (isset($request['deleteIds'])) {
            $deleteIds = $request['deleteIds'];
            unset($request['deleteIds']);
        }

        if (isset($request['categoryIds'])) {
            $categoryIds = $request['categoryIds'];
            unset($request['categoryIds']);
            $this->setCategories($productId, $categoryIds);
        }
        if (isset($request['prices'])) {
            $prices = $request['prices'];
            unset($request['prices']);
            $this->setPrices($productId, $prices);
        }

        $request = $this->dataBase->stripAll((array)$request);
        if (isset($request['price'])) {
            $request['price'] = $request['price'] * 1;
        }
        if (isset($request['nds'])) {
            $request['nds'] = $request['nds'] * 1;
        }
        if (isset($request['ndsMode'])) {
            $request['ndsMode'] = $request['ndsMode'] * 1;
        }
        if (isset($request['isPopular'])) {
            $request['isPopular'] = $request['isPopular'] == 'true';

            if ($request['isPopular'] && (!isset($request['popularOrder']) || $request['popularOrder'] == null)) {
                $request['popularOrder'] = count($this->getPopular(true));
            }
        }

        if (isset($request['isPopular']) && !$request['isPopular']) {
            $request['popularOrder'] = null;
        }
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $productId);
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        $this->setPhotos($productId, $deleteIds, $photos);


        return true;
    }

    public function delete($productId)
    {
        $this->unsetPhotos($productId, true);
        $this->unsetItems($productId, "ProductCategory");
        $this->unsetItems($productId, "ProductPrice");
        $query = "delete from " . $this->table . " where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($productId));
        return true;
    }

    public function sortProducts($products)
    {
        foreach ($products as $product) {
            $query = "update ProductCategory set productOrder=? where id=?";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute(array($product['productOrder'], $product['productCategoryId']));
        }
        return true;
    }

    public function sortPopularProducts($products)
    {
        foreach ($products as $product) {
            $query = "update Product set popularOrder=? where id=?";
            $stmt = $this->dataBase->db->prepare($query);
            $stmt->execute(array($product['order'], $product['id']));
        }
        return true;
    }

    private function setPhotos($productId, $deleteIds, $photos)
    {
        if ($deleteIds && count($deleteIds) > 0) {
            $this->unsetPhotos($deleteIds);
        }
        if (!isset($photos['photos'])) {
            return;
        }
        $photos = $photos['photos'];

        $res = $this->fileUploader->upload($photos, 'Images', uniqid());
        if (is_array($res)) {
            foreach ($res as $key => $imagePath) {
                $values = array("productId" => $productId, "src" =>  DataBase::$baseUrl . $imagePath);
                $query = $this->dataBase->genInsertQuery($values, "ProductImage");
                $stmt = $this->dataBase->db->prepare($query[0]);
                if ($query[1][0]) {
                    $stmt->execute($query[1]);
                }
            }
        } else {
            $values = array("productId" => $productId, "src" =>  DataBase::$baseUrl . $res);
            $query = $this->dataBase->genInsertQuery($values, "ProductImage");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }

        return $res;
    }

    private function setCategories($productId, $categoryIds)
    {
        $this->unsetItems($productId, "ProductCategory", $categoryIds);
        foreach ($categoryIds as $value) {
            $categoryProducts = $this->category->readProductsSimple($value);
            $maxOrder = $this->max_attribute_in_array($categoryProducts, "productOrder");
            // $hasCategory = array_search($value, array_column($categoryProducts, 'categoryId'));
            $hasCategory = count(array_filter($categoryProducts, function ($p) use ($value, $productId) {
                return $p['categoryId'] == $value && $p['productId'] == $productId;
            })) > 0;
            $addedCount = 0;
            if ($hasCategory === false) {
                $addedCount += 1;
                $values = array("productId" => $productId, "categoryId" =>  $value, "productOrder" => $maxOrder + $addedCount);
                $query = $this->dataBase->genInsertQuery($values, "ProductCategory");
                $stmt = $this->dataBase->db->prepare($query[0]);
                if ($query[1][0]) {
                    $stmt->execute($query[1]);
                }
            }
        }
    }

    private function max_attribute_in_array($array, $prop)
    {
        if ($array && count($array) > 0) {
            return max(array_map(
                function ($o) use ($prop) {
                    return $o[$prop];
                },
                $array
            ));
        }

        return 1;
    }

    private function setPrices($productId, $prices)
    {
        $this->unsetItems($productId, "ProductPrice");
        foreach ($prices as $value) {
            $value = json_decode($value, true);
            $value['productId'] = $productId;
            $query = $this->dataBase->genInsertQuery($value, "ProductPrice");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }
    }

    private function unsetItems($productId, $table, $activeCategoryIds = [])
    {
        $query = "delete from $table where productId=?";
        if ($activeCategoryIds && count($activeCategoryIds)) {
            $ids = implode(", ", $activeCategoryIds);
            $query = $query . " AND categoryId NOT IN ($ids);";
        }
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($productId));
    }

    private function unsetPhotos($ids, $all = false)
    {
        $stmt = null;
        if ($all) {
            $stmt = $this->dataBase->db->query("select src from ProductImage where productId='$ids'");
        } else {
            $ids = implode(", ", $ids);
            $stmt = $this->dataBase->db->query("select src from ProductImage where id IN ($ids)");
        }

        while ($url = $stmt->fetch()) {
            $this->fileUploader->removeFile($url['src'], DataBase::$baseUrl);
        }

        if ($all) {
            $stmt = $this->dataBase->db->query("delete from ProductImage where productId='$ids'");
        } else {
            $stmt = $this->dataBase->db->query("delete from ProductImage where id IN ($ids)");
        }



        return true;
    }

    public function getPhotos($productId, $firstOnly = false)
    {
        $stmt = null;
        if ($firstOnly) {
            $stmt = $this->dataBase->db->prepare("select id, src from ProductImage where productId=? LIMIT 1");
        } else {
            $stmt = $this->dataBase->db->prepare("select id, src from ProductImage where productId=? ORDER BY sortOrder");
        }
        $stmt->execute(array($productId));

        return $stmt->fetchAll();
    }

    public function getPrice($productId)
    {
        $res = [];
        $stmt = $this->dataBase->db->prepare("select * from ProductPrice where productId=?");
        $stmt->execute(array($productId));
        while ($price = $stmt->fetch()) {
            $price['id'] = $price['id'] * 1;
            $price['price'] = $price['price'] * 1;
            $price['countFrom'] = $price['countFrom'] * 1;
            $res[] = $price;
        }

        return $res;
    }

    public function getCurrentPrice($productId)
    {
        $query = "SELECT price FROM Product WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($productId));
        $price = $stmt->fetch();
        return $price['price'];
    }

    public function readSimle($id)
    {
        $query = "SELECT p.id, p.boxId, p.name, p.volume, p.coefficient, p.price FROM Product p WHERE p.id = ? LIMIT 1";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $product = $stmt->fetch();
        $product['category'] = $this->category->readFirst($product['id']);
        $product['prices'] = $this->getPrice($product['id']);

        return $product;
    }
}
