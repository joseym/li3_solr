<?php

use \lithium\core\Libraries;
use \lithium\net\http\Media;


/**
 * Useful Constants
 */
if (!defined('SOLARIUM_PATH')) define('SOLARIUM_PATH', dirname(__DIR__) . "/libraries/solarium");

/**
 * Load 3rd party library `Solarium` into the plugin
 * @link http://www.solarium-project.org https://github.com/basdenooijer/solarium
 */
Libraries::add("solarium", array(
	"prefix" => "Solarium_",
	"path" => SOLARIUM_PATH . "/library/Solarium/",
	"bootstrap" => "Autoloader.php",
	"loader" => array("Solarium_Autoloader", "load")
));
