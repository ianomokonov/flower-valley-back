<?php
class FilesUpload{
    private $baseUrl = 'http://stand1.progoff.ru/api/';
    public function upload($file, $path, $prefix){
        if(!file_exists($path)){
            mkdir($path);
        }
        if($file != null){
            $n = basename($prefix."_".$file['name']);
            $d = $path."/".$n;
            if(move_uploaded_file($file['tmp_name'], $d)){
                return($this->baseUrl.$d);
            }else{
                throw new Exception('Отсутствует имя файла', 400);
            }
        }
    }

    public function removeFile($filelink){
        $path = explode($this->baseUrl, $filelink);
        if(isset($path[1]) && file_exists($path[1])){
            unlink($path[1]);
        }
    }
}
