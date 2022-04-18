<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
require_once __DIR__ . '/sale.php';
require_once __DIR__ . '/product.php';
class StaticModel
{
    private $dataBase;

    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
        $this->fileUploader = new FilesUpload();
    }

    public function read()
    {
        $product = new Product($this->dataBase);
        $sale = new Sale($this->dataBase);
        $sales = $this->readStatic(4, false);
        $sales['items'] = $sale->getList();
        $result = array(
            'main' => $this->readStatic(1),
            'comments' => $this->readStatic(2),
            'clients' => $this->readStatic(3),
            'sales' => $sales,
            'videos' => $this->readVideos(),
            'media' => $this->readMedia(),
            'popular' => $product->getPopular(),
        );


        return $result;
    }



    private function readStatic($id, $getPhotos = true)
    {
        $query = "SELECT * FROM Static WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $static = $stmt->fetch();
        if (!$static) {
            return null;
        }
        $result = $this->setNumId($static);
        if ($getPhotos) {
            $result['photos'] = $this->readPhotos($id);
        }

        return $result;
    }

    public function updateStatic($id, $request, $files)
    {
        $deleteIds = [];
        if (isset($request['deleteIds'])) {
            $deleteIds = $request['deleteIds'];
            unset($request['deleteIds']);
        }

        if (count($request)) {
            $request = $this->dataBase->stripAll((array)$request, true);
            $request['autoPlay'] = $request['autoPlay'] * 1;
            $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true' || $request['isUserCanLeaf'] == true;
            $query = $this->dataBase->genUpdateQuery($request, 'Static', $id);

            $stmt = $this->dataBase->db->prepare($query[0]);
            $stmt->execute($query[1]);
        }

        $this->setPhotos($id, $deleteIds, $files);

        return true;
    }

    private function readPhotos($id)
    {
        $query = "SELECT id, src FROM StaticPhoto WHERE staticId=? ORDER BY sortOrder";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));

        return $this->setNumIds($stmt->fetchAll());
    }

    private function readVideos()
    {
        $query = "SELECT * FROM Video ORDER BY sortOrder";
        $stmt = $this->dataBase->db->query($query);

        return $this->setNumIds($stmt->fetchAll());
    }

    public function readVideoById($id)
    {
        $query = "SELECT * FROM Video WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));

        $video = $stmt->fetch();

        if (!$video) {
            return null;
        }

        return $video;
    }

    public function createVideo($request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genInsertQuery($request, 'Video');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function updateVideo($id, $request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genUpdateQuery($request, 'Video', $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteVideo($id)
    {
        $query = "delete from Video where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    private function readMedia()
    {
        $query = "SELECT * FROM Media ORDER BY sortOrder";
        $stmt = $this->dataBase->db->query($query);

        return $stmt->fetchAll();
    }

    public function readMediaById($id)
    {
        $query = "SELECT * FROM Media WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));

        $media = $stmt->fetch();

        if (!$media) {
            return null;
        }

        return $media;
    }

    public function createMedia($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        if ($file) {
            $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        }

        $query = $this->dataBase->genInsertQuery($request, 'Media');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function updateMedia($id, $request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        if ($file) {
            $this->removeImg('Media', $id);
            $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        }

        $query = $this->dataBase->genUpdateQuery($request, 'Media', $id);
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteMedia($id)
    {
        $this->removeImg('Media', $id);
        $query = "delete from Media where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    public function readDiscounts()
    {
        $query = "SELECT * FROM Discount";
        $stmt = $this->dataBase->db->query($query);

        return $stmt->fetchAll();
    }

    public function readDiscountById($id)
    {
        $query = "SELECT * FROM Discount WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));

        $media = $stmt->fetch();

        if (!$media) {
            return null;
        }

        return $media;
    }

    public function createDiscount($request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $query = $this->dataBase->genInsertQuery($request, 'Discount');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function updateDiscount($id, $request)
    {
        $request = $this->dataBase->stripAll((array)$request, true);

        $query = $this->dataBase->genUpdateQuery($request, 'Discount', $id);
        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteDiscount($id)
    {
        $query = "delete from Discount where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    public function readMenuItems()
    {
        $query = "SELECT * FROM MenuItem";
        $stmt = $this->dataBase->db->query($query);

        return $stmt->fetchAll();
    }

    public function updateMenuItems($items)
    {
        $query = "delete from MenuItem";
        $this->dataBase->db->query($query);

        foreach ($items as $item) {
            $query = $this->dataBase->genInsertQuery($item, 'MenuItem');
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }

        return $this->readMenuItems();
    }

    public function readContactPhotos()
    {
        $query = "SELECT * FROM ContactPhoto ORDER BY sortOrder";
        $stmt = $this->dataBase->db->query($query);

        return $this->setNumIds($stmt->fetchAll());
    }

    public function createContactPhoto($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        $query = $this->dataBase->genInsertQuery($request, 'ContactPhoto');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function updateContactPhoto($id, $request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        if ($file) {
            $this->removeImg('ContactPhoto', $id);
            $request['img'] = DataBase::$baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        }
        $query = $this->dataBase->genUpdateQuery($request, 'ContactPhoto', $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteContactPhoto($id)
    {
        $this->removeImg('ContactPhoto', $id);
        $query = "delete from ContactPhoto where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    public function updateStaticValue($id, $value, $files)
    {
        $query = "select * from StaticValue WHERE id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $curValue = $stmt->fetch();
        if (isset($files['value'])) {
            $value = DataBase::$baseUrl . $this->fileUploader->upload($files['value'], 'StaticFiles', uniqid());
        }
        if ($curValue) {
            $query = $this->dataBase->genUpdateQuery(array('value' => $value), 'StaticValue', $id);
            if (isset($files['value'])) {
                $this->removeImg('StaticValue', $id, 'value');
            }
        } else {
            $query = $this->dataBase->genInsertQuery(array('value' => $value, 'id' => $id), 'StaticValue');
        }

        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0] != null) {
            $stmt->execute($query[1]);
        }


        return $value;
    }

    public function getStaticValues($ids)
    {
        $ids = implode(", ", $ids);
        $stmt = $this->dataBase->db->query("select * from StaticValue where id IN ($ids)");
        return $stmt->fetchAll();
    }

    private function removeImg($table, $id, $fileField = 'img')
    {
        $object = $this->readObj($table, $id);
        if (!$object[$fileField]) {
            return;
        }

        $this->fileUploader->removeFile($object[$fileField], DataBase::$baseUrl);
    }

    private function readObj($table, $id)
    {
        $query = "SELECT * FROM " . $table . " WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return $stmt->fetch();
    }

    private function setPhotos($id, $deleteIds, $photos)
    {
        if ($deleteIds && count($deleteIds) > 0) {
            $this->unsetPhotos($deleteIds);
        }
        if (!$photos || !isset($photos['photos'])) {
            return;
        }
        $photos = $photos['photos'];

        $res = $this->fileUploader->upload($photos, 'MainImages', uniqid());
        if (is_array($res)) {
            foreach ($res as $imagePath) {
                $values = array("staticId" => $id, "src" =>  DataBase::$baseUrl . $imagePath);
                $query = $this->dataBase->genInsertQuery($values, "StaticPhoto");
                $stmt = $this->dataBase->db->prepare($query[0]);
                if ($query[1][0]) {
                    $stmt->execute($query[1]);
                }
            }
        } else {
            $values = array("staticId" => 1, "src" =>  DataBase::$baseUrl . $res);
            $query = $this->dataBase->genInsertQuery($values, "StaticPhoto");
            $stmt = $this->dataBase->db->prepare($query[0]);
            if ($query[1][0]) {
                $stmt->execute($query[1]);
            }
        }

        return $res;
    }

    private function unsetPhotos($ids)
    {
        $ids = implode(", ", $ids);
        $stmt = $this->dataBase->db->query("select src from StaticPhoto where id IN ($ids)");
        while ($url = $stmt->fetch()) {
            $this->fileUploader->removeFile($url['src'], DataBase::$baseUrl);
        }

        $stmt = $this->dataBase->db->query("delete from StaticPhoto where id IN ($ids)");

        return true;
    }

    private function setNumIds($arr)
    {
        $res = [];
        foreach ($arr as $obj) {
            $res[] = $this->setNumId($obj);
        }

        return $res;
    }

    private function setNumId($obj)
    {
        $obj['id'] = $obj['id'] * 1;
        if (isset($obj['isUserCanLeaf'])) {
            $obj['isUserCanLeaf'] = $obj['isUserCanLeaf'] == '1';
        }
        if (isset($obj['autoPlay'])) {
            $obj['autoPlay'] = $obj['autoPlay'] * 1;
        }

        return $obj;
    }
}
