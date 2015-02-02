<?php
namespace EuropeanaBatchExport;
use ZipArchive;

class XmlRecordStorage implements RecordStorageInterface {

  private $request_uniqid;
  private $folder_path;

  public function init(array $options = array()){
    global $config;

    //preparation
    $this->request_uniqid = uniqid();
    $this->folder_path = $config['xml_folder_path'].'/' . date( 'Y-m-d_H.i.s' ) . '_' . $this->request_uniqid;
    if (!file_exists($this->folder_path)) {
        mkdir($this->folder_path, 0777, true);
    } else {
      throw new Exception("Folder creation error", 1);
    }
  }

  public function addMultiple(array $records, $index){
    $zip_name =  $this->request_uniqid . '_'. $index . '.zip';
    $zip = new ZipArchive;
    $res = $zip->open($this->folder_path.'/'.$zip_name, ZipArchive::CREATE);
    if ($res !== TRUE) {
      print('zip failed');
      print($res);
    }

    $counter = 0;
    foreach ($records as $record) {
      $record_as_array =  get_object_vars($record);
      $filtered_record_array = array_filter($record_as_array, function($x) {
        return (!empty($x));
      });
      $xml = XMLSerializer::generateValidXmlFromArray($filtered_record_array);

      $file_name = str_replace('/', '_', $record->id);
      $zip->addFromString($file_name.'.xml', $xml);

      $counter++;
    }

    $zip->close();
    return $counter;
  }

  public function flush(){
    //
  }
  
}

class XMLSerializer {

  // functions adopted from http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/

  public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
    $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

    $xml .= '<' . $node_block . '>';
    $xml .= self::generateXmlFromArray($array, $node_name);
    $xml .= '</' . $node_block . '>';

    return $xml;
  }

  private static function generateXmlFromArray($array, $node_name) {
    $xml = '';

    if (is_array($array) || is_object($array)) {
      foreach ($array as $key=>$value) {
        if (is_numeric($key)) {
          $key = $node_name;
        }

        $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
      }
    } else {
      $xml = htmlspecialchars($array, ENT_QUOTES);
    }

    return $xml;
  }
}