<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
require_once __DIR__ . '/product.php';
require_once __DIR__ . '/box.php';
class Order
{
    private $dataBase;
    private $product;
    private $box;
    private $table = '`Order`';

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->product = new Product($dataBase);
        $this->box = new Box($dataBase);
    }

    public function read($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $order = $stmt->fetch();
        $order['deliveryWishDateFrom'] = $order['deliveryWishDateFrom'] ? date("Y-m-d\TH:i:00.000\Z", strtotime($order['deliveryWishDateFrom'])) : null;
        $order['deliveryWishDateTo'] = $order['deliveryWishDateTo'] ? date("Y-m-d\TH:i:00.000\Z", strtotime($order['deliveryWishDateTo'])) : null;
        $order['confirmedDeliveryDate'] = $order['confirmedDeliveryDate'] ? date("Y-m-d\TH:i:00.000\Z", strtotime($order['confirmedDeliveryDate'])) : null;
        $order['products'] = $this->getProducts($id);
        $order['boxes'] = $this->getBoxes($id);


        return $order;
    }

    public function getList($skip, $take, $str, $status)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE (id LIKE '%$str%' OR clientInn LIKE '%$str%' OR clientPhone LIKE '%$str%' OR clientEmail LIKE '%$str%' OR clientName LIKE '%$str%')";
        if ($status) {
            $query .= " AND status = $status";
        }

        $query .= " ORDER BY orderDate DESC LIMIT $skip, $take";
        $stmt = $this->dataBase->db->query($query);
        return $stmt->fetchAll();
    }

    public function update($id, $request)
    {
        if (isset($request['boxes'])) {
            $this->removeItems($id, 'OrderBox');
            $this->addItems($request['boxes'], 'OrderBox', $id, 'boxId');
            unset($request['boxes']);
        }
        if (isset($request['products'])) {
            $this->removeItems($id, 'OrderProduct');
            $this->addItems($request['products'], 'OrderProduct', $id, 'productId');
            unset($request['products']);
        }
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function create($request)
    {
        $products = [];
        $boxes = [];
        if (isset($request['boxes'])) {
            $boxes = $request['boxes'];
            unset($request['boxes']);
        }
        if (isset($request['products'])) {
            $products = $request['products'];
            unset($request['products']);
        }
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        $id = $this->dataBase->db->lastInsertId();
        $this->addItems($boxes, 'OrderBox', $id, 'boxId');
        $this->addItems($products, 'OrderProduct', $id, 'productId');
        return $id;
    }

    private function getProducts($orderId)
    {
        $query = "SELECT * FROM OrderProduct WHERE orderId = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($orderId));
        $result = [];
        while ($product = $stmt->fetch()) {
            $product['product'] = $this->product->readSimle($product['productId']);
            $result[] = $product;
        }

        return $result;
    }

    private function getBoxes($orderId)
    {
        $query = "SELECT * FROM OrderBox WHERE orderId = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($orderId));
        $result = [];
        while ($box = $stmt->fetch()) {
            $box['box'] = $this->box->read($box['boxId']);
            $result[] = $box;
        }

        return $result;
    }

    private function addItems($items, $table, $orderId, $idCol)
    {
        if (!$items || count($items) == 0) {
            return;
        }
        foreach ($items as $item) {

            $item[$idCol] = $item['id'];
            $item = $this->dataBase->stripAll((array)$item, true);
            $item['orderId'] = $orderId;
            $query = $this->dataBase->genInsertQuery($item, $table);
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }
    }

    private function removeItems($orderId, $table, $ids = [])
    {
        $query = "DELETE FROM $table WHERE orderId = ?";
        if (count($ids) > 0) {
            $ids = implode(", ", $ids);
            $query .= " AND id IN ($ids);";
        }
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($orderId));
    }
}
