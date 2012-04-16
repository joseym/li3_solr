<?php

namespace li3_solr\extensions\adapter\data\source\http;

use lithium\core\ConfigException;
use lithium\core\NetworkException;
use lithium\util\String;
use lithium\util\Inflector;
use Solarium_Client;
use li3_solr\vendors\QueryBuilder\Querystring;
use li3_solr\vendors\QueryBuilder\Field;

class Solr extends \lithium\data\source\Http {

	/**
	 * Going to store Solarium class here
	 * @var object
	 */
	public $connection = null;
	public $query = null;
	public $queryString = null;

	/**
	 * Classes used by `Solr`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'service' => 'lithium\net\http\Service',
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'array' => 'lithium\data\collection\DocumentArray'
	);

	protected $_handlers = array();

	/**
	 * Constructor.
	 * @param array $config
	 */
	public function __construct(array $config = array()) {

		$defaults = array('port' => 8983, 'path' => '/solr/');

		$adapter = array();

		$adapter['adapteroptions'] = $config + $defaults;

		parent::__construct($adapter);

	}

	public function connect() {

		$this->_isConnected = false;

		try {

			$this->connection = new Solarium_Client($this->_config);
			$this->connection->setAdapter('Solarium_Client_Adapter_Http');
			$this->_isConnected = true;

		} catch(SolrException $e) {

			throw new NetworkException('Could not connect to the database.', 503, $e);

		}

		$this->queryString = new Querystring();

		return $this->_isConnected;

	}

	/**
	 * Ensures that the server connection is closed and resources are freed when the adapter
	 * instance is destroyed.
	 *
	 * @return void
	 */
	public function __destruct() {

		if (!$this->_isConnected) {
			return;
		}

		$this->disconnect();
		unset($this->connection);

	}

	/**
	 * Magic for passing methods to http service.
	 *
	 * @param string $method
	 * @param array $params
	 * @return void
	 */
	public function __call($method, $params = array()) {
		list($path, $data, $options) = ($params + array('/', array(), array()));
		return json_decode($this->connection->{$method}($path, $data, $options));
	}

	/**
	 * Returns an array of object types accessible through this database.
	 *
	 * @param object $class
	 * @return void
	 */
	public function sources($class = null) {

	}

	/**
	 * Create new document.
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function create($query, array $options = array()) {

	}

	/**
	 * Read from document.
	 *
	 * @param string $query
	 * @param array $options
	 * @return object
	 * @filter
	 */
	public function read($query, array $options = array()) {

		// localize `$this` variables for use within the filter closure
		$_connection = $this->connection;
			$this->query = $_connection->createSelect();
		$_query = $this->query;
		$_classes = $this->_classes;

		$defaults = array();

		$options += $defaults;

		$params = compact('query', 'options', '_query', '_connection');
		$_config = $this->_config;

		$filter = function($self, $params) use ($_config, $_classes) {

			extract($params, EXTR_OVERWRITE); // `$query`, `$options`, `$_query`, `$_connection`

			$args = $query->export($self);

			// Apply Query filters
			// Only accepted if `query` isn't passed to find condition
			if(!empty($args['fields'])) 	$args['fields'];
			if(!empty($args['limit'])) 		$args['limit'];
			if(!empty($args['range'])) 		$args['range'];
			if(!empty($args['facets'])) 	$args['facets'];
			if(!empty($args['query']))  	$_query->setQuery($args['query']);
			if(!isset($args['query']) 
				AND !empty($args['conditions'])) $_query->setQuery($args['conditions']);
			
			echo($args['conditions']);

			$data = array();

			$results = $_connection->select($_query);

			$data['count'] = $results->getNumFound();

			if( isset($args['facets']) AND $args['facets']['object'] ) {

				$facets = $results->getFacetSet();
				
				$result = new $_classes['entity'];

				foreach ($facets as $key => $filter) {

					// $facet = array();
					$facet = new $_classes['entity'];

				    foreach($filter AS $value => $count){
						if($value === "") $value = "_undefined";
				    	$facet->{$value} = $count;
				    }

				    $result->{$key} = $facet;

				}

			    $data['facets'] = $result;

			}

			foreach ($results as $document) {

				$result = new $_classes['entity'];

			    // the documents are also iterable, to get all fields
			    foreach($document AS $field => $value){
			    	$result->{$field} = $value;
			    }

			    $data['results'][] = $result;

			}

			// $stats += array('total_rows' => null, 'offset' => null);
			// $opts = compact('stats') + array('class' => 'set', 'exists' => true);
			
			return $self->item($query->model(), $data);

		};

		return $this->_filter(__METHOD__, $params, $filter);

	}


	/**
	 * Update document.
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function update($query, array $options = array()) {

	}

	/**
	 * Delete document.
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function delete($query, array $options = array()) {

	}

	/**
	 * Fields for query.
	 *
	 * @param string $fields
	 * @param string $context
	 * @return array
	 */
	public function fields($fields, $context) {
		return $this->query->setFields($fields) ?: array();
	}

	/**
	 * Limit for query.
	 *
	 * @param string $limit
	 * @param string $context
	 * @return array
	 */
	public function limit($limit, $context) {

		$range = array('start' => 0, 'length' => $limit);
		return $this->range($range, $context);

	}

	/**
	 * Return a range of results
	 * @param  array $range
	 * @param  object $context
	 * @return array
	 */
	public function range(array $range = array(), $context) {
		
		$start = $range['start'];
		$length = isset($range['length']) ? $range['length'] : null;

		// Determine limit length
		// If both `start` and `end` are passed then we calculate how many
		// items to walk
		if(array_key_exists('start', $range) AND array_key_exists('end', $range)){
			$length = $range['end'] - $range['start'];
		}

		return $this->query->setStart($start)->setRows($length);

	}

	/**
	 * Order for query.
	 *
	 * @param string $order
	 * @param string $context
	 * @return array
	 */
	function order($order, $context) {
		return (array) $order ?: array();
	}

	/**
	 * Manual Query.
	 *
	 * @param string $query
	 * @param string $context
	 * @return array
	 */
	function query($query, $context) {
		return $query ?: null;
	}

	/**
	 * Facets for query.
	 *
	 * @param string $facet
	 * @param string $context
	 * @return array
	 */
	function facets($facets, $context) {

		// remember aliases and fields for faceting
		$facetset['fields'] = $facets;
		// pass faceting off to solarium
		$facetset['object'] = $this->query->getFacetSet();

		foreach($facetset['fields'] as $field => $value){
			$facetset['object']->createFacetField($field)->setField($value);
		}

		return $facetset ?: null;
	}

	function conditions($conditions, $context){
		return $this->_buildQuery($conditions) ?: null;
	}

	private function array_implode( $glue, $separator, $array ) {

	    if ( ! is_array( $array ) ) return $array;
	    $string = array();
	    foreach ( $array as $key => $val ) {
	        if ( is_array( $val ) )
	            $val = implode( ',', $val );
	        $string[] = "{$key}{$glue}{$val}";
	       
	    }
	    return implode( $separator, $string );
	   
	}

	/**
	 * Recursive function used to build smarty solr queries from an array of conditions
	 * - does not currently work, use `query` option with standard solr query formatting
	 * @param  array  $conditions
	 * @param  array  $options    could include parent array key, current key and the querybuilding object
	 * @return string             query string
	 * @see li3_solr\vendors\QueryBuilder\Querystring;
	 * @see li3_solr\vendors\QueryBuilder\Field;
	 */
	private function _buildQuery(array $conditions = array(), array $options = array()){

		$defaults = array(
			'queryObject' 	=> $this->queryString,
			'parent' 		=> array(),
			'key' 			=> null
		);

		$options += $defaults;

		$masterObject = $options['queryObject'] === $this->queryString;

		$is_assoc = function($array) {
			return (bool) count(array_filter(array_keys($array), 'is_string'));
		};

		// print_r($options['parent']);

		foreach($conditions as $field => $entry){

			if($options['key'] === null) $options['key'] = $field;

			if(is_array($entry)){

				$subParams = array();

				$subQuery = new Querystring;
				$subParams['queryObject'] = $subQuery;
				// $subParams = array('queryObject' => $this->queryString);

				$key = array_keys($entry); $key = $key[0];

				if(!$is_assoc($entry)){
					$subParams['key'] = $field;
				}

				if($field == 'AND' OR $field == 'OR'){

					// print_r($field); print_r($entry);

					$options['parent'] = $field;
					$subParams += $options;
					$subQuery->setFieldSeparator($field);

				}
				
				$options['queryObject']->setFieldSeparator($field);

				$this->_buildQuery($entry, $subParams);

				$options['queryObject']->addSubQuery($subQuery, $key);

			} else {

				// $this->queryString->addField(new Field($options['key'], $entry));
				$options['queryObject']->addField(new Field($options['key'], $entry));

			}

		}

		// echo $this->queryString;

		return $this->queryString;
	}


}

?>