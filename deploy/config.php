<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

argument('dir', InputArgument::OPTIONAL, 'Run tasks only on this host or stage.');
option('b', null, InputOption::VALUE_OPTIONAL, 'Define git branch');
option('migration', null, InputOption::VALUE_OPTIONAL, 'Migrate or not', false);
//https://www.hashbangcode.com/article/adding-arguments-and-options-deployer-tasks

require_once 'recipe/deploy/check_remote.php';
$dep = require_once 'vars.php';

define('RED', 31);
define('BG_RED', 41);
define('GREEN', 32);
define('BG_GREEN', 42);
define('YELLOW', 33);
define('BG_YELLOW', 43);

//class dep {
//	static $vars = [
//		'app' => 'otp',
//		'base_dir' => '/var/www/otp/',
//		'local_config_dir' => 'current/deploy/',
////		'config_dir' => '/var/www/otp/production/current/deploy',
//		'branches' => ['master', 'dev'],
//		'dir' => ['dev', 'production', 'stage'],
//		'mess_prod_only' => 'Task blocked. Production only',
//	];
//}


class dep {
	static $vars = [];
}

dep::$vars = $dep;

set('application', dep::$vars['app']);
set('shared_files', ['env.ini', 'app/migration/migration.log', 'composer.json']); //symlink to copy whole dir //, 'composer.lock'
set('shared_dirs', ['sounds', 'app/ravan/supervisor_conf']);
set('repository', 'git@github.com:arhivator1337/otp.git');
set('git_tty', true);
set('keep_releases', 5);
set('writable_mode', 'chmod');
set('writable_chmod_mode', '777');
set('writable_dirs', ['tmp', 'tmp/cache/', 'sounds', '/tmp/otp/', 'app/migration/', 'app/otp/supervisor_conf/']);
set('allow_anonymous_stats', false);

task('deploy', [
	'deploy:info',
	'deploy:check_remote',
	'deploy:prepare',
	'deploy:lock',
	'deploy:release',
	'deploy:update_code',
	'deploy:shared',
	'deploy:writable',
	'deploy:vendors',
	'deploy:clear_paths',
//    'deploy:erase_cache',
	'deploy:symlink',
	'deploy:migrations',
	'deploy:unlock',
	'cleanup',
	'restart_env',
	'success'
]);

before('rollback', 'rollback_info');

task('rollback_info', function () {
	out("Start rollback: {{application}} | {{branch}} | {{deploy_path}} | {{config_path}}" , BG_GREEN);
});

task('deploy:info', function () {
	$tag = null;
	if (input()->hasOption('tag'))
		$tag = input()->getOption('tag');

	if (input()->hasOption('migration'))
		set('no_migration', input()->getOption('migration') == 'no' ? true : false);
	else
		set('no_migration', false);

	set('deploy_path', function () {
		if (input()->hasArgument('dir')) {
			if (!empty(input()->getArgument('dir')))
				return get('deploy_dir') . askChoice('Choose dir:', dep::$vars['dir'], null);
		}
		return get('deploy_dir'); // . 'production';
	});

	$dirs = explode('/', get('deploy_path'));
	foreach ($dirs as $dir) {
		if($dir != '')
			$new_dir[] = $dir;
	}

	$deploy_type = $new_dir[count($new_dir)-2] ?: 'dev';

	set('deploy_type', $deploy_type);
	set('production_only',  $deploy_type == 'production' ? 'production_only' : $deploy_type);

	set('branch', function () {
		if (input()->hasOption('b')) {
			if(input()->getOption('b') == 'choose')
				return askChoice('Choose branch!', dep::$vars['branches'], null);
			elseif(empty(input()->getOption('b')) && in_array('--b', $_SERVER['argv']))
				return askChoice('Choose branch!', dep::$vars['branches'], null);
			elseif(!empty(input()->getOption('b')))
				return input()->getOption('b');
		}
		return 'main';
	});

	out("Start deploy app: {{application}} | branch: {{branch}} | path:{{deploy_path}} | cfg: {{config_path}} | deploy_type: {{deploy_type}} | {{production_only}}; tag:" . $tag, BG_GREEN);
});

task('restart_env', function () {
	if(get('production_only') == false)
		return out(dep::$vars['mess_prod_only'], BG_YELLOW);

	if(file_exists('/.dockerenv')) {
//		run('supervisorctl restart manager');
//		run('supervisorctl restart otp:*');
//		out("Docker: Supervisor opt group restarted", BG_GREEN);
	}
	else {
		run('service php7.2-fpm restart');
		out("PHP is restarted", BG_RED);
	}
	//run('supervisorctl restart php-fpm');
});

task('deploy:migrations', function () {
	if(get('no_migration')) {
		out("No migration", BG_YELLOW);
		return true;
	}
//	'current_path', dep::$vars['base_dir'] . "/renero_stage");
	$result = run('cd {{deploy_path}}/current && php index.php scripts/migration/run');
	out("Migration start:", BG_YELLOW);
	out($result, YELLOW);

	out("Migration end", BG_YELLOW);
});


task('success', function () {
	out("Deployed", BG_GREEN);
});

after('deploy:failed', 'deploy:unlock');

function out($message, $color = 40) {
	writeln("\e[{$color}m" . $message . "\e[0m");
}