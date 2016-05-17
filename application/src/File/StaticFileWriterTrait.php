<?php

namespace Omeka\File;


trait StaticFileWriterTrait {
  protected static $filewriter;

  static public function setFileWriter($filewriter) {
    self::$filewriter = $filewriter;
  }


  static public function getFileWriter() {
    if (!isset(self::$filewriter))
      self::$filewriter = new FileWriter();
    return self::$filewriter;
  }
}

?>