<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
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
        $this->sale = new Sale($dataBase);
        $this->category = new Category($this->dataBase);
    }

    public function search($str)
    {
        $str = htmlspecialchars(strip_tags($str));
        $query = "SELECT DISTINCT p.id, p.name, p.price, p.boxId FROM Product p WHERE p.name LIKE '%$str%' OR p.description LIKE '%$str%'";

        $stmt = $this->dataBase->db->query($query);

        $result = [];

        while ($p = $stmt->fetch()) {
            $c = $this->category->readFirst($p['id']);
            $p['categoryId'] = $c['id'];
            $p['categoryName'] = $c['name'];
            $p['price'] = $p['price'] * 1;
            $p['boxId'] = $p['boxId'] * 1;
            $p['sale'] = $this->sale->getSale($p['id'], false);
            $result[] = $p;
        }

        return $result;
    }

    public function getPopular()
    {
        $query = "SELECT p.id, p.name, p.price, p.boxId, p.coefficient FROM Product p WHERE p.isPopular";

        $stmt = $this->dataBase->db->query($query);
        $result = [];

        while ($p = $stmt->fetch()) {
            $p['price'] = $p['price'] * 1;
            $p['boxId'] = $p['boxId'] * 1;
            $p['prices'] = $this->getPrice($p['id']);
            $p['photos'] = $this->getPhotos($p['id'], true);
            $c = $this->category->readFirst($p['id']);
            $p['categoryId'] = $c['id'];
            $p['categoryName'] = $c['name'];
            $p['coefficient'] = $p['coefficient'] * 1;
            $p['sale'] = $this->sale->getSale($p['id'], false);
            $result[] = $p;
        }

        return $result;
    }




    public function send($request)
    {
        $subject = "Заказ цветов";
        $message = "<p>Супер заказ цветов на миллион денег</p>
        <p><a href='" . $request['link'] . "'>Ссылка</a></p>";


        $headers  = "Content-type: text/html; charset=utf-8 \r\nFrom: info@progoff.ru\r\n";
        // $headers  = "Content-type: text/html; charset=utf-8 \r\n";

        mail($request['email'], $subject, $message, $headers);
        mail('Volik9925@yandex.ru', $subject, $message, $headers);
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
        $product['boxId'] = $product['boxId'] * 1;
        $product['coefficient'] = $product['coefficient'] * 1;
        $product['isPopular'] = $product['isPopular'] == '1';
        $product['photos'] = $this->getPhotos($id);
        $product['categories'] = $this->getCategories($id);
        $product['sale'] = $this->sale->getSale($product['id'], false);

        return $product;
    }

    public function create($request, $photos)
    {
        $categoryIds = $request['categoryIds'];
        $prices = $request['prices'];
        unset($request['categoryIds']);
        unset($request['prices']);
        $request = $this->dataBase->stripAll((array)$request);
        $request['price'] = $request['price'] * 1;
        $request['nds'] = $request['nds'] * 1;
        $request['ndsMode'] = $request['ndsMode'] * 1;
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }
        $this->setPhotos($request['id'], [], $photos);
        $this->setCategories($request['id'], $categoryIds);
        $this->setPrices($request['id'], $prices);


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
        $request['price'] = $request['price'] * 1;
        $request['nds'] = $request['nds'] * 1;
        $request['ndsMode'] = $request['ndsMode'] * 1;
        $request['isPopular'] = $request['isPopular'] == 'true';
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
                $values = array("productId" => $productId, "src" =>  $this->dataBase->baseUrl . $imagePath);
                $query = $this->dataBase->genInsertQuery($values, "ProductImage");
                $stmt = $this->dataBase->db->prepare($query[0]);
                if ($query[1][0]) {
                    $stmt->execute($query[1]);
                }
            }
        } else {
            $values = array("productId" => $productId, "src" =>  $this->dataBase->baseUrl . $res);
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
        $this->unsetItems($productId, "ProductCategory");
        foreach ($categoryIds as $value) {
            $values = array("productId" => $productId, "categoryId" =>  $value);
            $query = $this->dataBase->genInsertQuery($values, "ProductCategory");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }
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

    private function unsetItems($productId, $table)
    {
        $stmt = $this->dataBase->db->prepare("delete from $table where productId=?");
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
            $this->fileUploader->removeFile($url['src'], $this->dataBase->baseUrl);
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
        $res = [];
        $stmt = null;
        if ($firstOnly) {
            $stmt = $this->dataBase->db->prepare("select src from ProductImage where productId=? LIMIT 1");
        } else {
            $stmt = $this->dataBase->db->prepare("select id, src from ProductImage where productId=?");
        }
        $stmt->execute(array($productId));
        while ($url = $stmt->fetch()) {
            $url['id'] = $url['id'] * 1;
            $res[] = $url;
        }

        return $res;
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

    private function getCategories($productId)
    {
        $res = [];
        $stmt = $this->dataBase->db->prepare("select c.id, c.name, c.parentId, c.img, c.isTulip  from ProductCategory pc join Category c on c.id = pc.categoryId where productId=?");
        $stmt->execute(array($productId));
        while ($category = $stmt->fetch()) {
            $category['id'] = $category['id'] * 1;
            $parent = $this->category->readParentCategory($category['id']);
            $category['isTulip'] = $parent['isTulip'];
            if ($category['isTulip']) {
                $category['steps'] = $this->category->readSteps($parent['id']);
            }

            $category['parentId'] = $category['parentId'] * 1;
            $res[] = $category;
        }

        return $res;
    }
}
