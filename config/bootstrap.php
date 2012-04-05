<?php

use \lithium\core\Libraries;
use \lithium\net\http\Media;


/**
 * Useful Constants
 */
if (!defined('SOLARIUM_PATH')) define('SOLARIUM_PATH', dirname(__DIR__) . "/libraries/solarium");

print_r(SOLARIUM_PATH);

Libraries::add("solarium", array(
    "includePath" => SOLARIUM_PATH . "/library",
    "bootstrap" => "/Solarium/Autoloader.php",
	"loader" => array("Solarium_Autoloader")
));
