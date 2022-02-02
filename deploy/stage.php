<?php

namespace Deployer;

require_once 'recipe/common.php';
$cfg_file = 'config.php';
$project_dir = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR;
$git_deployed_config = 'production/current/deploy/' . $cfg_file;

$config_path = file_exists($project_dir . $git_deployed_config) ? $project_dir . $git_deployed_config :  __DIR__ . DIRECTORY_SEPARATOR . $cfg_file;
$config_path = $cfg_file;
require $config_path;

set('config_path', $config_path);
set('deploy_path', $project_dir . 'stage');
set('deploy_dir', $project_dir );