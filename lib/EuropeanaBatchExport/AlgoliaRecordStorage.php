<?php 
namespace EuropeanaBatchExport;
use AlgoliaSearch;

require 'algoliasearch-client-php/algoliasearch.php';

class AlgoliaRecordStorage implements RecordStorageInterface {

	private $batch = array();
  private $index;

	public function init(array $options = array()){
    global $config;
		$client = new AlgoliaSearch\Client($config['algolia_application_id'], $config['algolia_admin_api_key']); //algolia_application_id,algolia_admin_api_key
    $this->index = $client->initIndex($config['algolia_index']);//$algolia_index
	}

	public function addMultiple(array $records, $index){
    $counter=0;

    foreach ($records as $record) {
      $record_as_array =  get_object_vars($record);
      $filtered_record_array = array_filter($record_as_array, function($x) {
        return (!empty($x));
      });
      $filtered_record_array['objectID'] = $filtered_record_array['id'];
      array_push($this->batch, $filtered_record_array);

      $counter++;
    }

    if (count($this->batch) == 10000) {
      $this->flash();
    }

    return $counter;
	}

	public function flush(){
    $this->index->saveObjects($this->batch);
    $this->batch = array();
  }
	
}