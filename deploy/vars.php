<?php
return [
		'app' => 'otp',
		'base_dir' => '/var/www/otp/',
		'local_config_dir' => 'current/deploy/',
//		'config_dir' => '/var/www/otp/production/current/deploy',
		'branches' => ['master', 'dev'],
		'dir' => ['dev', 'production', 'stage'],
		'mess_prod_only' => 'Task blocked. Production only',
	];