<?php 
namespace EuropeanaBatchExport;

use \Europeana\Api\Helpers\Response as Response_Helper;
use \Europeana\Api\Helpers\Request as Request_Helper;

include 'config.php';

class BatchJob {

  const ALGOLIA_STORAGE = 0;
  const XML_STORAGE = 1;

  private $query_string;
  private $init;
  private $rows = 100; //Maximum permitted
  private $wskey = '';
  private $record_storage;

  public $total_records_found = 0;
  public $total_records_count = 0;

  public function __construct( $query_string, $init=1 ){
    
    // clean-up query
    $query_string = filter_var( $query_string, FILTER_SANITIZE_STRING );
    $query_string = Request_Helper::normalizeQueryString( $query_string );

    // remove rows param from query - we're going to ignore user provided value
    $query_string = Request_Helper::removeQueryParam( $query_string, 'rows' );

    // remove start param from the query
    $this->query_string = Request_Helper::removeQueryParam( $query_string, 'start' );
    $this->init = (int) Request_Helper::getQueryParam( $query_string, 'start', $init );
    $this->record_storage = new XmlRecordStorage();//Default
    $this->wskey = $europeana_key;
    var_dump($europeana_key);die;
  }

  public function setStorage($storage){
    if ( $storage === self::ALGOLIA_STORAGE ){
      $this->record_storage = new AlgoliaRecordStorage();
    } elseif ( $storage === self::XML_STORAGE ){
      $this->record_storage = new XmlRecordStorage();
    } else {
      throw new Exception("Invalid Storage option", 1);
    }
  }

  public function harvestRecords(){
    //prepare storage
    $this->record_storage->init(array());

    $start = $this->init;
    //iterate to get all records 
    while( !empty($records = $this->getRecords($start))){
      $counter = $this->record_storage->addMultiple($records,$start);// returns how many records have been added
      $this->total_records_count += $counter;
      //Finished?
      if ($this->total_records_found+1 == $start+$counter){ //+1 because of europeana bug
        $this->record_storage->flush();
        break;
      }

      $start += $this->rows;
    }

    return $this->total_records_count;
  }

  private function getRecords($start){

    // set search options
    $search_request_options = array(
      'query' => $this->query_string,
      'rows' => $this->rows,
      'start' => $start,
      'wskey' => $this->wskey
    );

    try {
      // set-up the search
      $curl = new \Libcurl\Curl();
      $curl->setHttpHeader( array( 'Accept: application/json' ) );
      $search_request_options['RequestService'] = $curl;
      $search_request = new \Europeana\Api\Request\Search( $search_request_options );
      // make the call
      $search_response = new \Europeana\Api\Response\Search( $search_request->call(), $this->wskey );

      if ($this->total_records_found === 0) {
        $this->total_records_found = $search_response->totalResults;
      }

      return $search_response->items;

    } catch (Exception $e) {
      $msg = 'error: %s';
      $parts = explode( 'Array', $e->getMessage(), 2 );
      if ( count( $parts ) === 2 ) {
        $result .= sprintf( $msg, nl2br( $parts[0] ) );
        $result .= 'prettyerror:' . Response_Helper::obfuscateApiKey( $parts[1], $wskey ) . '</>';
      } else {
        $result .= sprintf( $msg, Response_Helper::obfuscateApiKey( $e->getMessage(), $wskey ) );
      }
    }
  }
}