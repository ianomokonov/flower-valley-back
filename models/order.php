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
    private $table = 'Order';

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
        $order['products'] = $this->getProducts($id);
        $order['boxes'] = $this->getBoxes($id);


        return $order;
    }

    public function getList($str, $skip, $take)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id LIKE '%$str%' OR clientInn LIKE '%$str%' OR clientPhone LIKE '%$str%' OR clientEmail LIKE '%$str%' OR clientName LIKE '%$str%' LIMIT $skip, $take;";
        $stmt = $this->dataBase->db->query($query);
        return $stmt->fetchAll();
    }

    public function update($id, $request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genUpdateQuery($request, $this->table, $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function create($request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genInsertQuery($request, $this->table);
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
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
}
