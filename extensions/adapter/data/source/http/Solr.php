<?php

namespace li3_solr\extensions\adapter\data\source\http;

use lithium\core\ConfigException;
use lithium\core\NetworkException;
use lithium\util\String;
use lithium\util\Inflector;
use Solarium_Client;

class Solr extends \lithium\data\source\Http {

	/**
	 * Going to store Solarium class here
	 * @var object
	 */
	public $connection = null;
	public $query = null;

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
		// print_r($options);
		$_connection = $this->connection;
		$this->query = $_connection->createSelect();
		$_query = $this->query;


		
		$defaults = array();

		$options += $defaults;

		$params = compact('query', 'options', '_query', '_connection');
		$_config = $this->_config;

		$filter = function($self, $params) use ($_config) {

			extract($params, EXTR_OVERWRITE); // `$query`, `$options`, `$_query`, `$_connection`

			$args = $query->export($self);

			// Apply field filter
			if(!empty($fields)) $args['fields'];

			$data = array();

			$results = $_connection->select($_query);

			// $data['count'] = $data['results']->getNumFound();

			foreach ($results as $document) {

				$result = new \lithium\core\Object;

			    // the documents are also iterable, to get all fields
			    foreach($document AS $field => $value){
			    	$result->{$field} = $value;
			    }

			    $data[] = $result;

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
	 * Handle conditions.
	 *
	 * @param string $conditions
	 * @param string $context
	 * @return array
	 */
	public function conditions($conditions, $context) {
		return array($conditions);
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
		
		return compact('limit') ?: array();

	}

	/**
	 * Return a range of results
	 * @param  array $range
	 * @param  object $context
	 * @return array
	 */
	public function range(array $range = array(), $context) {
		return compact('range') ?: array();
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
	 * Facets for query.
	 *
	 * @param string $facet
	 * @param string $context
	 * @return array
	 */
	function facets($facets, $context) {
		$facetset = $this->query->getFacetSet();
		foreach($facets as $field => $value){
			$facetset->createFacetField($field)->setField($value);
		}
		return $facetset ?: null;
	}

}

?>