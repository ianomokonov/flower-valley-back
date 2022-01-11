<?php
require_once __DIR__ . '/../utils/database.php';
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

    public function search($str)
    {
        $str = htmlspecialchars(strip_tags($str));
        $query = "SELECT DISTINCT p.id, p.name, p.price, c.id as categoryId, c.name as categoryName, p.boxId FROM Product p LEFT JOIN ProductCategory pc ON pc.productId = p.id LEFT JOIN Category c ON c.id = pc.categoryId WHERE p.name LIKE '%$str%' OR p.description LIKE '%$str%'";

        $stmt = $this->dataBase->db->query($query);

        $result = [];

        while ($p = $stmt->fetch()) {
            $p['price'] = $p['price'] * 1;
            $p['categoryId'] = $p['categoryId'] * 1;
            $p['boxId'] = $p['boxId'] * 1;
            $result[] = $p;
        }

        return $result;
    }

    public function getPopular()
    {
        $query = "SELECT p.id, p.name, p.price, c.id as categoryId, c.name as categoryName, p.boxId, p.coefficient FROM Product p LEFT JOIN ProductCategory pc ON pc.productId = p.id LEFT JOIN Category c ON c.id = pc.categoryId WHERE p.isPopular";

        $stmt = $this->dataBase->db->query($query);

        $result = [];

        while ($p = $stmt->fetch()) {
            $p['price'] = $p['price'] * 1;
            $p['categoryId'] = $p['categoryId'] * 1;
            $p['boxId'] = $p['boxId'] * 1;
            $p['coefficient'] = $p['coefficient'] * 1;
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
        $product['nds'] = $product['nds'] * 1;
        $product['ndsMode'] = $product['ndsMode'] * 1;
        $product['boxId'] = $product['boxId'] * 1;
        $product['coefficient'] = $product['coefficient'] * 1;
        $product['isPopular'] = $product['isPopular'] == '1';
        $product['photos'] = $this->getPhotos($id);
        $product['categories'] = $this->getCategories($id);

        return $product;
    }

    public function create($request, $photos)
    {
        $categoryIds = $request['categoryIds'];
        unset($request['categoryIds']);
        $request = $this->dataBase->stripAll((array)$request);
        $request['price'] = $request['price'] * 1;
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }
        $this->setPhotos($request['id'], $photos);
        $this->setCategories($request['id'], $categoryIds);


        return $request['id'];
    }

    public function update($productId, $request, $photos)
    {
        unset($request['id']);
        if (isset($request['categoryIds'])) {
            $categoryIds = $request['categoryIds'];
            unset($request['categoryIds']);
            $this->setCategories($productId, $categoryIds);
        }

        $request = $this->dataBase->stripAll((array)$request);
        $request['price'] = $request['price'] * 1;
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $productId);
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        $this->setPhotos($productId, $photos);


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
        if (!isset($photos['photos'])) {
            return;
        }
        $photos = $photos['photos'];
        $this->unsetPhotos($productId);

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
        $this->unsetCategories($productId);
        foreach ($categoryIds as $value) {
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

    public function getPhotos($productId)
    {
        $res = [];
        $stmt = $this->dataBase->db->prepare("select src from ProductImage where productId=?");
        $stmt->execute(array($productId));
        while ($url = $stmt->fetch()) {
            $res[] = $url['src'];
        }

        return $res;
    }

    private function getCategories($productId)
    {
        $res = [];
        $stmt = $this->dataBase->db->prepare("select c.id, c.name, c.parentId, c.img from ProductCategory pc join Category c on c.id = pc.categoryId where productId=?");
        $stmt->execute(array($productId));
        while ($category = $stmt->fetch()) {
            $category['id'] = $category['id'] * 1;
            $category['parentId'] = $category['parentId'] * 1;
            $res[] = $category;
        }

        return $res;
    }
}
