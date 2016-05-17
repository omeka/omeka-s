<?php
namespace Omeka\File;

class FileWriter {

    public function putContents($path, $contents) {
        return file_put_contents($path, $contents);
    }

    public function fileExists($path) {
        return file_exists($path);
    }

    public function dirExists($path) {
        return is_dir($path);
    }

    public function mkdir($directory_name) {
        return mkdir($directory_name,0777,true);

    }

    public function getContents($path) {
        return file_get_contents($path);
    }


    public function moveUploadedFile($source,$destination) {
        return move_uploaded_file($source,$destination);
    }


    public function rename($oldname, $newname) {
        return rename($oldname,$newname);
    }
}

?>