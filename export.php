<?php
set_include_path(
  'lib' . PATH_SEPARATOR .
  get_include_path()
);

function __autoload( $class_name ) {
  $include_path = str_replace( array( '\\', '_', '..', '.' ), array( '/' ), $class_name );
  include $include_path . '.php';
}
require 'config.php';
 
$query = 'query=*%3A*&profile=rich&qf=COUNTRY%3Agreece&qf=-UGC%3Atrue&reusability=open,restricted';
$batch = new EuropeanaBatchExport\BatchJob($query,188000);
$batch->setStorage(EuropeanaBatchExport\BatchJob::ALGOLIA_STORAGE);
$total = $batch->harvestRecords();
echo $total." records processed".PHP_EOL;
