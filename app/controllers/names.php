<?php
namespace controllers;

use controllers\system\name_generator;
use helpers\validate;

class names extends \controllers\Controller {

	public function __construct() {
		parent::__construct();
		$this->app->set('breadcrumb_no_default_bread', 1);
		$this->names = new \controllers\system\name_generator();
	}

	public function index(\Base $app, $param) {

		foreach (array_keys($this->names->names) as $tier)
			$links["/names/get_names/{$tier}/all/5000"] = $tier . ' all 5k';

		$links['/names/generate_nicknames/8000'] = 'Nicknames';
		$links['/names/generate_user_agents/8000'] = 'User Agents';

		$app->mset([
			'content' => 'names.html',
			'data' => $links,
		]);

		$this->render();
	}

	public function get_names(\Base $app, $params) {
		if($params['param1'] == 'no_ru')
			unset($this->names['russia'], $this->names['russia_ru']);

		if(isset($params['param1']) && in_array($params['param1'], array_keys($this->names->names)))
			$tier = $params['param1'];
		else
			$tier = 'all';

		if(in_array($params['param2'], ['male', 'female', 'all']))
			$gender = $params['param2'];
		else
			$gender = 'all';

		$limit = validate::filter('int', $params['param3']) ?: 1000;

//		echo "<b>tier:</b> {$tier}<br>";
//		echo '<br>';

		$names = $this->names->generate_name($tier, $gender, $limit);
		for ($i = 0; $i < count($names); $i++)
			echo $names[$i] . '<br>';
	}

	public function generate_nicknames(\Base $app, $params) {
		$limit = validate::filter('int', $params['param1']) ?: 1000;
		$nicks = $this->names->generate_nickname($limit);
		for ($i = 0; $i < count($nicks); $i++)
			echo $nicks[$i] . '<br>';
	}

	public function generate_user_agents(\Base $app, $params) {
		$limit = validate::filter('int', $params['param1']) ?: 1000;
		$data = $this->names->generate_user_agents($limit);

		for ($i = 0; $i < count($data); $i++)
			echo $data[$i] . '<br>';
	}

}