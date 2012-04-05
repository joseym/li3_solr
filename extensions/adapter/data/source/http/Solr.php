<?php

namespace li3_solr\extensions\adapter\data\source\http;

use lithium\core\ConfigException;
use lithium\util\String;
use lithium\util\Inflector;
use li3_solr\vendors\solarium\library\Solarium\Autoloader;

class Solr extends \lithium\data\source\Http {

	/**
	 * Increment value of current result set loop
	 * used by `result` to handle rows of json responses.
	 *
	 * @var string
	 */
	protected $_iterator = 0;

	/**
	 * True if Database exists.
	 *
	 * @var boolean
	 */
	protected $_db = false;

	/**
	 * Classes used by `CouchDb`.
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

}

?>