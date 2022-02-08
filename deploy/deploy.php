<?php

namespace Deployer;

$dep = include 'vars.php';
require_once 'recipe/common.php';

$config = 'config.php';

require $config_path = file_exists($dep['base_dir'] . '/production/' . $dep['local_config_dir'] . $config) ? $dep['base_dir'] . '/production/' . $dep['local_config_dir'] . $config : __DIR__ . DIRECTORY_SEPARATOR . $config;
set('config_path', $config_path);