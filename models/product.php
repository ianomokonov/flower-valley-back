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

    public function read($id)
    {
        $query = "SELECT * FROM Product p WHERE p.id=? ";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $product['id'] = $product['id'] * 1;
        $product['price'] = $product['price'] * 1;
        $product['nds'] = $product['nds'] * 1;
        $product['ndsMode'] = $product['ndsMode'] * 1;
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
        $this->setPhotos($request['id'], $photos['photos']);
        $this->setCategories($request['id'], $categoryIds);


        return $request['id'];
    }

    public function update($productId, $request, $photos)
    {
        unset($request['id']);
        $categoryIds = $request['categoryIds'];
        unset($request['categoryIds']);
        $request = $this->dataBase->stripAll((array)$request);

        $query = $this->dataBase->genUpdateQuery($request, $this->table, $productId);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        $this->setPhotos($productId, $photos['photos']);
        $this->setCategories($productId, $categoryIds);

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
        foreach ($categoryIds as $key => $value) {
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
