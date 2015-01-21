<?php 
namespace EuropeanaBatchExport;

interface RecordStorageInterface{
	
	public function init(array $options = array());

	public function addMultiple(array $items, $index);

	public function flush();

} 