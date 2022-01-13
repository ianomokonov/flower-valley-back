<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/filesUpload.php';
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
        $result = array(
            'main' => $this->readStatic(1),
            'videos' => $this->readVideos(),
            'comments' => $this->readStatic(2),
            'clients' => $this->readStatic(3),
            'popular' => $product->getPopular(),
        );


        return $result;
    }



    private function readStatic($id)
    {
        $query = "SELECT * FROM Static WHERE id = ?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        $static = $stmt->fetch();
        if (!$static) {
            return null;
        }
        $result = $this->setNumId($static);
        $result['photos'] = $this->readPhotos($id);
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
            $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true';
            $query = $this->dataBase->genUpdateQuery($request, 'Static', $id);

            $stmt = $this->dataBase->db->prepare($query[0]);
            $stmt->execute($query[1]);
        }

        $this->setPhotos($id, $deleteIds, $files);

        return true;
    }

    private function readPhotos($id)
    {
        $query = "SELECT id, src FROM StaticPhoto WHERE staticId=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));

        return $this->setNumIds($stmt->fetchAll());
    }

    private function readVideos()
    {
        $query = "SELECT * FROM Video";
        $stmt = $this->dataBase->db->query($query);

        return $this->setNumIds($stmt->fetchAll());
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

    public function readContactPhotos()
    {
        $query = "SELECT * FROM ContactPhoto";
        $stmt = $this->dataBase->db->query($query);

        return $this->setNumIds($stmt->fetchAll());
    }

    public function createContactPhoto($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request);
        $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
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
            $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
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

    private function removeImg($table, $id)
    {
        $object = $this->readObj($table, $id);
        if (!$object['img']) {
            return;
        }

        $this->fileUploader->removeFile($object['img'], $this->dataBase->baseUrl);
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
        if (!$photos || !isset($photos['photos'])) {
            return;
        }
        $photos = $photos['photos'];
        if ($deleteIds && count($deleteIds) > 0) {
            $this->unsetPhotos($deleteIds);
        }


        $res = $this->fileUploader->upload($photos, 'MainImages', uniqid());
        if (is_array($res)) {
            foreach ($res as $imagePath) {
                $values = array("staticId" => $id, "src" =>  $this->dataBase->baseUrl . $imagePath);
                $query = $this->dataBase->genInsertQuery($values, "StaticPhoto");
                $stmt = $this->dataBase->db->prepare($query[0]);
                if ($query[1][0]) {
                    $stmt->execute($query[1]);
                }
            }
        } else {
            $values = array("staticId" => 1, "src" =>  $this->dataBase->baseUrl . $res);
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
            $this->fileUploader->removeFile($url['src'], $this->dataBase->baseUrl);
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
