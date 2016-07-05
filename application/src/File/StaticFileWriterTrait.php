<?php
namespace Omeka\File;

trait StaticFileWriterTrait {
  protected static $filewriter;

  public static function setFileWriter($filewriter) {
    self::$filewriter = $filewriter;
  }


  public static function getFileWriter() {
    if (!isset(self::$filewriter))
      self::$filewriter = new FileWriter();
    return self::$filewriter;
  }
}
