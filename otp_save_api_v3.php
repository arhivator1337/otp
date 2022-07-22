<?php
$connect_sql = true;

$app = include ($base_dir = __DIR__ . '/'). 'app/ravan/app.php';
include $base_dir . 'app/controllers/application.php';
include $base_dir . 'app/controllers/api.php';

$app->api = new \controllers\api_v3(true);

$app->cfg['module'] = 'otp_save_apiv3';
$app->cfg['base_dir'] = $base_dir;

$log_file = $app->cfg[$app->cfg['module'] . '_log'] ? $app->cfg['log_dir'] . $app->cfg[$app->cfg['module'] . '_log'] : $app->cfg['log_dir'] . $app->cfg['module'] . '.log';
ini_set('error_log',  $log_file . '.error');

$date = date('Y-m-d H:i:s') . ': ';

define('DEBUG', $app->cfg['debug']);

if($argv[1] == 'log_all')
	$app->cfg['debug'] = 3;

$app->log = \ravan\log::instance($log_file);

if($argv[1] == 'log_file') {
	ravan\out('php error log: ' . $log_file);
	ravan\out_fatal($app->cfg['module'] . 'log: ' . $app->log->log_file);
}

if(!(($number = intval($argv[1])) > 0)) {
	$app->log->add($date . 'ERROR: no number');
	$app->log->add($date . print_r($argv, 1));
	ravan\out_fatal('ERROR: no number');
}

$time = time() - $app->api->time_limit;
$new_time = time();

$res = $app->db->sql($sql = "select * from {$app->api->db_numbers} where number = {$number} and date >= {$time} order by date desc limit 1");
ravan\out($sql);
$app->log->add($date . $log = "number called: {$number} | id in db: {$res[0]['id']}");
ravan\out($log);

$sql = "insert into {$app->api->db_number_requests} set number_id = {$res[0]['id']}, type = 1, date = " . time();

$app->db->sql($sql);