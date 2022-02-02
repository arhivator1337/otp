<?php

$app = include ($base_dir = __DIR__ . '/'). 'app/ravan/app.php';
include $base_dir . 'app/controllers/api.php';
$app->api = new \controllers\api(true);

$app->cfg['module'] = 'otp_save';
$app->cfg['base_dir'] = $base_dir;

$log_file = $app->cfg[$app->cfg['module'] . '_log'] ? $app->cfg['log_dir'] . $app->cfg[$app->cfg['module'] . '_log'] : $app->cfg['log_dir'] . $app->cfg['module'] . '.log';
ini_set('error_log',  $log_file . '.error');

$date = date('Y-m-d H:i:s') . ': ';

define('DEBUG', $app->cfg['debug']);

$request = $argv[2];
if($request == 'log_all')
	$app->cfg['debug'] = 3;

$app->log = \ravan\log::instance($log_file);

if(!($number = $argv[1])) {
	$app->log->add($date . 'ERROR: no number');
	$app->log->add($date . print_r($argv, 1));
	ravan\out_fatal('ERROR: no number');
}

$time = time() - $app->api->time_limit;
$new_time = time();
$result = $app->db->sql("update {$app->api->db_numbers} set status=status+1, date = {$new_time} where number = {$number} and date >= {$time} order by date asc limit 1;");

if($request == 'log_file') {
	ravan\out('php error log: ' . $log_file);
	ravan\out_fatal($app->cfg['module'] . 'log: ' . $app->log->log_file);
}

$app->log->add($date . 'number called: ' . $number);