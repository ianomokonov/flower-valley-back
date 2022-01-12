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
        $query = "SELECT * FROM Static";
        $stmt = $this->dataBase->db->query($query);
        $product = new Product($this->dataBase);
        $static = $stmt->fetch();
        $static['isUserCanLeaf'] = $static['isUserCanLeaf'] == '1';
        $static['photos'] = $this->readPhotos();
        $result = array(
            'main' => $static,
            'videos' => $this->readVideos(),
            'comments' => $this->readComments(),
            'clients' => $this->readClients(),
            'popular' => $product->getPopular(),
        );


        return $result;
    }

    public function updateMain($request, $files)
    {
        if (count($request)) {
            $request = $this->dataBase->stripAll((array)$request, true);
            $request['autoPlay'] = $request['autoPlay'] * 1;
            $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true';
            $query = $this->dataBase->genUpdateQuery($request, 'Static', 1);

            $stmt = $this->dataBase->db->prepare($query[0]);
            $stmt->execute($query[1]);
        }

        $this->setPhotos($files);

        return true;
    }

    private function readPhotos()
    {
        $query = "SELECT id, src FROM StaticPhoto";
        $stmt = $this->dataBase->db->query($query);

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

    public function createComment($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $request['autoPlay'] = $request['autoPlay'] * 1;
        $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true';
        $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        $query = $this->dataBase->genInsertQuery($request, 'Comment');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    private function readComments()
    {
        $query = "SELECT * FROM Comment";
        $stmt = $this->dataBase->db->query($query);

        return $this->setNumIds($stmt->fetchAll());
    }

    public function updateComment($id, $request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $request['autoPlay'] = $request['autoPlay'] * 1;
        $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true';
        if ($file) {
            $this->removeImg('Comment', $id);
            $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        }
        $query = $this->dataBase->genUpdateQuery($request, 'Comment', $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteComment($id)
    {
        $this->removeImg('Comment', $id);
        $query = "delete from Comment where id=?";
        $stmt = $this->dataBase->db->prepare($query);
        $stmt->execute(array($id));
        return true;
    }

    private function readClients()
    {
        $query = "SELECT * FROM Client";
        $stmt = $this->dataBase->db->query($query);

        return $this->setNumIds($stmt->fetchAll());
    }

    public function createClient($request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $request['autoPlay'] = $request['autoPlay'] * 1;
        $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true';
        $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        $query = $this->dataBase->genInsertQuery($request, 'Client');
        $stmt = $this->dataBase->db->prepare($query[0]);
        if ($query[1][0]) {
            $stmt->execute($query[1]);
        }

        return $this->dataBase->db->lastInsertId();
    }

    public function updateClient($id, $request, $file)
    {
        $request = $this->dataBase->stripAll((array)$request, true);
        $request['autoPlay'] = $request['autoPlay'] * 1;
        $request['isUserCanLeaf'] = $request['isUserCanLeaf'] == 'true';
        if ($file) {
            $this->removeImg('Client', $id);
            $request['img'] = $this->dataBase->baseUrl . $this->fileUploader->upload($file, 'MainImages', uniqid());
        }
        $query = $this->dataBase->genUpdateQuery($request, 'Client', $id);

        $stmt = $this->dataBase->db->prepare($query[0]);
        $stmt->execute($query[1]);

        return true;
    }

    public function deleteClient($id)
    {
        $this->removeImg('Client', $id);
        $query = "delete from Client where id=?";
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

    private function setPhotos($photos)
    {
        if (!isset($photos['photos'])) {
            return;
        }
        $photos = $photos['photos'];
        $this->unsetPhotos(1);

        $res = $this->fileUploader->upload($photos, 'MainImages', uniqid());
        if (is_array($res)) {
            foreach ($res as $key => $imagePath) {
                $values = array("staticId" => 1, "src" =>  $this->dataBase->baseUrl . $imagePath);
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

    private function unsetPhotos($id)
    {
        $stmt = $this->dataBase->db->prepare("select src from StaticPhoto where staticId=?");
        $stmt->execute(array($id));
        while ($url = $stmt->fetch()) {
            $this->fileUploader->removeFile($url['src'], $this->dataBase->baseUrl);
        }

        $stmt = $this->dataBase->db->prepare("delete from StaticPhoto where staticId=?");
        $stmt->execute(array($id));

        return true;
    }

    private function setNumIds($arr)
    {
        $res = [];
        foreach ($arr as $obj) {
            $obj['id'] = $obj['id'] * 1;
            if (isset($obj['isUserCanLeaf'])) {
                $obj['isUserCanLeaf'] = $obj['isUserCanLeaf'] == '1';
            }
            $res[] = $obj;
        }

        return $res;
    }
}