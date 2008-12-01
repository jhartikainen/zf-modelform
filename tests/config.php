<?php

error_reporting(E_ALL|E_STRICT);

date_default_timezone_set('GMT');

$paths = array(
	'../library',
	'../../zend_trunk/library',
	'../../doctrine/lib',
	'models',
	'models/generated',
	get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader.php';

define('SANDBOX_PATH', dirname(__FILE__));
define('DOCTRINE_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . '../../doctrine/lib');
define('DATA_FIXTURES_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'fixtures');
define('MODELS_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'models');
define('MIGRATIONS_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'migrations');
define('SQL_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sql');
define('YAML_SCHEMA_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'schema');
define('DB_PATH', SANDBOX_PATH . DIRECTORY_SEPARATOR . 'sandbox.db');
define('DSN', 'sqlite:test.db');

Zend_Loader::registerAutoload();
spl_autoload_register(array('Doctrine', 'autoload'));
