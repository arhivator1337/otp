<?php

namespace Deployer;
require_once 'recipe/common.php';

$cfg_file = 'config.php';
$dep = require_once 'vars.php';

$cfg_file = file_exists($dep['base_dir'] . 'production/' . $dep['local_config_dir'] . $cfg_file) ? $dep['base_dir'] . 'production/' . $dep['local_config_dir'] . $cfg_file : __DIR__ . DIRECTORY_SEPARATOR . $cfg_file;
$config_path = $cfg_file;
require $config_path;

set('config_path', $config_path);
set('deploy_path', $dep['base_dir'] . 'production');
set('deploy_dir', $dep['base_dir'] );