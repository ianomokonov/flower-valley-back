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
    private $mailer;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
        $this->sale = new Sale($dataBase);
        $this->category = new Category($this->dataBase);
        $this->mailer = new Mailer();
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
        $query = "SELECT p.id, p.name, p.price, p.boxId, p.coefficient FROM Product p WHERE p.isPopular ORDER BY p.popularOrder";

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
        $this->mailer->mail->Subject = "Заказ цветов";

        $message = "
        <h2>Заказ № " . strtoupper(uniqid()) . "</h2>
        <h3>Данные клиента</h3>
        <p>ФИО: " . $request['fullName'] . "</p>
        <p>Телефон: " . $request['phone'] . "</p>
        <p>Адрес доставки: " . $request['address'] . "</p>
        <hr/>
        <table>
        <thead>
        <tr>
            <td style='padding: 5px 10px'>Товар</td>
            <td style='padding: 5px 10px'>Цена</td>
            <td style='padding: 5px 10px'>Кол-во</td>
            <td style='padding: 5px 10px'>Стоимость</td>
        </tr>
        </thead>
        <tbody>
        ";
        $sum = 0;

        foreach ($request['products'] as $product) {
            $stmt = $this->dataBase->db->prepare("SELECT * FROM Product where id=?");
            $stmt->execute(array($product['id']));
            $p = $stmt->fetch();
            $sum += 1 * $product['count'] * $product['price'];
            $message = $message . "<tr>
                <td style='padding: 5px 10px'>" . $p['name'] . "</td>
                <td style='padding: 5px 10px'>" . $product['price'] . "</td>
                <td style='padding: 5px 10px'>" . $product['count'] . "</td>
                <td style='padding: 5px 10px'>" . $product['count'] * $product['price'] . "</td>
            </tr>";
        }
        $message = $message . "</tbody></table><hr/> <h3>Сумма товаров: " . $sum . "руб.</h3>";

        if (isset($request['boxesPrice'])) {
            $sum += 1 * $request['boxesPrice'];
            $message = $message . "<h3>Стоимость коробок: " . $request['boxesPrice'] . "руб.</h3>";
        }

        if (isset($request['deliveryPrice'])) {
            $sum += 1 * $request['deliveryPrice'];
            $message = $message . "<h3>Стоимость доставки: " . $request['deliveryPrice'] . "руб.</h3>";
        }

        $this->mailer->mail->Body = $message . "<h3>Сумма заказа: " . $sum . "руб.</h3>";
        $this->mailer->mail->addAddress($request['email']);
        $this->mailer->mail->send();
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
        $product['categories'] = $this->category->getProductCategories($id);
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
        $request['isPopular'] =  $request['isPopular'] == 'true';
        if ($request['isPopular'] && (!isset($request['popularOrder']) || $request['popularOrder'] == null)) {
            throw new Exception("Ошибка добавления товара. Укажите порядковый номер популярного товара.", 409);
        }
        if (!$request['isPopular']) {
            $request['popularOrder'] = null;
        }
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
        if ($request['isPopular'] && (!isset($request['popularOrder']) || $request['popularOrder'] == null)) {
            throw new Exception("Ошибка редактирования товара. Укажите порядковый номер популярного товара.", 409);
        }
        if (!$request['isPopular']) {
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

    public function getCurrentPrice($productId)
    {
        $query = "SELECT price FROM Product WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($productId));
        $price = $stmt->fetch();
        return $price['price'];
    }
}
