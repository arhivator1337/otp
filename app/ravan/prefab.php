<?
namespace ravan;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
set_time_limit(0);

define('C_RED', 31);
define('C_BG_RED', 41);
define('C_BG_RED_LIGHT', 101);
define('C_CYAN', 36);
define('C_BG_CYAN', 46);
define('C_BG_CYAN_LIGHT', 106);
define('C_GREY', 37);
define('C_BG_GREY', 100);
define('C_BG_WHITE', 107);
define('C_GREEN', 32);
define('C_BG_GREEN', 42);
define('C_BG_GREEN_LIGHT', 102);
define('C_BLUE', 34);
define('C_BG_BLUE', 44);
define('C_BG_BLUE_LIGHT', 104);
define('C_YELLOW', 33);
define('C_BG_YELLOW', 43);
define('C_BG_YELLOW_LIGHT', 103);

abstract class prefab {
	/**
	 *	Return class instance
	 *	@return static
	 **/
	static function instance() {
		if (!registry::exists($class=get_called_class())) {
			$ref=new \ReflectionClass($class);
			$args=func_get_args();
			registry::set($class,
				$args?$ref->newinstanceargs($args):new $class);
		}
		return registry::get($class);
	}

}

final class registry {

	static
		//! Object catalog
		$table;

	/**
	 *	Return TRUE if object exists in catalog
	 *	@return bool
	 *	@param $key string
	 **/
	static function exists($key) {
		return isset(self::$table[$key]);
	}

	/**
	 *	Add object to catalog
	 *	@return object
	 *	@param $key string
	 *	@param $obj object
	 **/
	static function set($key,$obj) {
		return self::$table[$key]=$obj;
	}

	/**
	 *	Retrieve object from catalog
	 *	@return object
	 *	@param $key string
	 **/
	static function get($key) {
		return self::$table[$key];
	}

	/**
	 *	Delete object from catalog
	 *	@param $key string
	 **/
	static function clear($key) {
		self::$table[$key]=NULL;
		unset(self::$table[$key]);
	}

	//! Prohibit cloning
	private function __clone() {
	}

	//! Prohibit instantiation
	private function __construct() {
	}

}
function out($message, $color = false, $date = true) {
	if(php_sapi_name() == 'cli')
		echo ($date ? date('y-m-d H:i:s') . ': ' : '');
	echo !is_scalar($message) ? colorize(print_r($message, 1), $color) . "\n" : colorize($message, $color) . "\n";
}

function out_h($header, $msg) {
	return out($header, C_BG_WHITE) . PHP_EOL . out($msg, false, false) . PHP_EOL;
}

function out_fatal($message) {
	echo out($message, C_RED);
	exit;
}

function colorize($str, $color = 40) {
	$prefix = "\e[{$color}m";
	$sufix =  "\e[0m";
	return $prefix . $str . $sufix;
}