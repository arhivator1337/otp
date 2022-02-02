<?php
require_once __DIR__ . '/vendor/autoload.php';

define('TTL_MONTH', 2592000);
define('TTL_DAY', 86400);

$lang = 'en'; //$app->get('default_language');

if(php_sapi_name() != "cli") {
	if($_SERVER['REQUEST_URI'])
		preg_match( "/^(\/([a-zA-Z]{2})\/).*$/", $_SERVER['REQUEST_URI'], $matches);

	if(!empty($matches)) {
		$lang = $matches[2];
		if ($matches[2])
			$_SERVER['REQUEST_URI'] = str_replace($matches[1], '/', $_SERVER['REQUEST_URI']);
	}
}

require 'lib/base.php';
require 'app/language.php';

$app = Base::instance();
$time_pre = microtime(true);

//preparation
$app->config('config.ini');
$app->config('otp.ini');

//$app->set('SERIALIZER', 'php'); //to avoid ig binary serialize
ini_set('error_log', $app->get('php_log'));

$app->set('LANGUAGE', $lang);
//$app->set('FALLBACK') ;
$app->set('lang', $lang);


if(filter_var($app->get('HOST'), FILTER_VALIDATE_URL) or filter_var($app->get('HOST'), FILTER_VALIDATE_IP))
	$_SERVER['HTTP_HOST'] = $app->get('HOST');
else
	$app->set('HOST', $_SERVER['HTTP_HOST']);


if($app->get('cache_driver') && $app->get('cache_host'))
	$app->set('CACHE', $app->get('cache_driver') . '=' . $app->get('cache_host'));

if(php_sapi_name() != "cli") {
	\helpers\auth::session_start();
	if($app->get('debug') == 3)
		Falsum\Run::handler(true);
}


$app->set('base_url', 'http://' . $_SERVER['HTTP_HOST'] . '/');
$app->set('request', $_REQUEST);
$app->set('start_time', $time_pre);
$app->set('current_url', $current_url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH));
$app->set('path', dirname(__FILE__));


if(!in_array($lang, $app->get('languages'))) {
	$lang = $app->get('default_language');
	$app->reroute($app->get('lang_base_url.' . $lang) . $current_url);
}

\helpers\l10n::$lang_code = $lang;

if($app->get('debug') == 3) {
	if($app->get('AJAX'))
		$app->set('ONERROR', function ($app) {die($app->get('ERROR.text'));});
	elseif(php_sapi_name() != "cli")
		Falsum\Run::handler(true);
}

define("ENV", $app->get('enviroment'));

if ((float)PCRE_VERSION<8.0)
	trigger_error('PCRE version is out of date');

$app->set("db", new DB\SQL(
	'mysql:host=' . $app->get('db_host') . ';port=3306;dbname=' . $app->get('db_name'),
	$app->get('db_user'),
	$app->get('db_pass')
));

$app->route('GET|POST /api_v2/@param1/@action/@param2', '\controllers\api_v2->@action');
$app->route('GET|POST /api_v2/@param1/@action/@param2/@param3', '\controllers\api_v2->@action');
$app->route('GET|POST /api_v2/@param1/@action/@param2/@param3/@param4', '\controllers\api_v2->@action');

$app->route('GET|POST /@controller', '\controllers\@controller->index');
$app->route('GET|POST /@controller/@action', '\controllers\@controller->@action');

$app->route('GET|POST /admin/@controller', '\controllers\admin\@controller->index');
$app->route('GET|POST /admin/@controller/@action', '\controllers\admin\@controller->@action');
$app->route('GET|POST /admin/@controller/@action/@param1', '\controllers\admin\@controller->@action');

$str = '';
for ($i = 1; $i <= 5; $i++) {
	$str.= '/@param' . $i;
	$route = 'GET|POST /@controller/@action' . $str;
	$app->route($route , '\controllers\@controller->@action');
}

$app->route('GET|POST|PUT /tasks/edit/@param1', '\controllers\tasks->edit');
$app->route('GET|POST /tasks/delete/@param1 [ajax]', '\controllers\tasks->delete');
//$app->route('GET /tasks/ivr_responses/@param1 [ajax]', '\controllers\tasks->ivr_response');

$app->route('GET /scripts/@controller/@action', '\scripts\@controller->@action');
$app->route('GET /scripts/@controller/@action/@param1', '\scripts\@controller->@action');
$app->route('GET /scripts/parser/run/@parser', '\scripts\parser->run');

$app->route('GET /server_1',
	function($app) {
		echo '<pre>';
		//var_dump($_SERVER);
		phpinfo();
	});
$app->route('GET /',
	function($app) {
		header("HTTP/1.1 500 Internal Server Error");
		echo '<h1>Something went wrong!</h1>';
		exit;
	});

$app->run();