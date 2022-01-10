<?php
class FilesUpload
{
    public function upload($file, $path, $prefix)
    {
        if (!file_exists($path)) {
            mkdir($path);
        }
        if ($file != null) {
            if (!is_array($file['name'])) {
                return $this->saveFile($file, $path, $prefix);
            }
            $urls = [];
            for ($i = 0; $i < count($file['name']); $i++) {
                $urls[] = $this->saveFile($file, $path, $prefix, $i);
            }

            return $urls;
        }
    }

    public function removeFile($filelink, $baseUrl)
    {
        $path = explode($baseUrl . '/', $filelink);
        if (isset($path[1]) && file_exists($path[1])) {
            unlink($path[1]);
        }
    }

    private function saveFile($file, $path, $prefix, $index = null)
    {
        $name = $index === null ? $file['name'] : $file['name'][$index];
        $tmp_name = $index === null ? $file['tmp_name'] : $file['tmp_name'][$index];
        $n = basename($prefix . "_" . $name);
        $d = $path . "/" . $n;
        if (move_uploaded_file($tmp_name, $d)) {
            return ("/" . $d);
        } else {
            throw new Exception('Отсутствует имя файла', 400);
        }
    }
}
