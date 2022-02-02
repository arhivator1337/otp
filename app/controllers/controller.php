<?php

namespace controllers;

use helpers\auth;
use helpers\html;
use helpers\l10n;

class Controller extends \application
{
	var $app, $user, $client_id = false;

	function __construct() {
		$this->app = \Base::instance();
		\helpers\auth::require_login();
	}

	function beforeRoute() {
		$this->app = \Base::instance();
		$this->app->set('class_name', $class_name =  (new \ReflectionClass($this))->getShortName());
		$this->app->set('class_pretty_name', $class_pretty_name = str_replace('_', ' ', $class_name));
//		$this->app->set('page_title', ucfirst($class_pretty_name));
		$this->app->set('page_title', __('main.' . $class_name));

		if(!$this->app->get('breadcrumb_no_default_bread')) //set this var to not show default breadcrumbs
			$this->add_breadcrumb();

		$this->user = \helpers\auth::get_user_data();
		$this->client_id = $this->user['client_id'];

		if($this->client_id) {
			$this->app->set('client_id', $this->client_id);
			$this->import_client_settings($this->client_id);
		}

		$this->app->set('log', ['initialized']);
    }

    protected function inject_client_id() {
	    $this->app->set('user', array_merge($this->app->get('SESSION.user'), ['client_id' => 1]));
    }

    protected function profiler() {
	    if(\helpers\auth::check_right(\helpers\auth::GROUP_SUPER_ADMIN)) {
		    $data = explode("\n", $this->app->get('db')->log());
		    $count_data = count($data)-1;
		    $data[] = 'total sql:' . $count_data;
		    $this->app->set('profiler', $data);
		    return $data;
	    }
    }

	protected function render($file = false, $mime = "text/html", array $hive = null, $ttl = 0, $return = false) {
		$this->app = \Base::instance();

		$this->app->mset(['app' => &$this->app, 'user' => auth::get_user_data()] );

		if($file == false)
			$file = $this->app->get('default_template_file');

		$this->default_html_vars();

		$this->profiler();
	    $this->log('render');

		$this->log('user.group' . $this->app->get('user.group'));
		$render = \View::instance()->render($file, $mime, $hive, $ttl);
		if($return)
			return $render;
		else
			echo $render;
    }

	protected function render_ajax($data) {
		echo json_encode($data);
		die;
	}

	protected function render_error($type = '500', $ajax = false) {
		header("HTTP/1.1 400 Bad Request");

		if($ajax) {
			echo json_encode(['error' => $type]);
			die;
		}
		$this->render("error_{$type}.html");
		die;
	}

	protected function log($message) {
		$this->app->push('log', $message);
	}

	protected function default_html_vars() {
		if($this->app->get('add_main_button') == 'add')
			$this->add_main_button(__('forms.add'), '/' . $this->app->get('class_name') . '/create');
	}

	public function add_breadcrumb($name = false, $link = false) {
		$admin_url = $this->app->get('breadcrumb_admin') == true ? '/admin' : '';
		if($name == false && $link == false)
			$this->app->push('breadcrumb.top', ['name' =>  __('menu.' . str_replace(' ', '_', $this->app->get('class_pretty_name'))), 'link' => $admin_url . '/' . $this->app->get('class_name')]);
		else
			$this->app->push('breadcrumb.top', ['name' => $name, 'link' => $link ? $admin_url . $link : '']);
	}

	public function add_main_button($name, $link = '#', $settings = []) {
		$this->app->push('breadcrumb.buttons', ['name' => $name, 'link' => $link, 'settings' => $settings]);
	}

//	public function import_client_settings($client_id) {
//		$settings = new \client_settings_model();
//		$this->app->set('settings', $settings->map_key_val($settings->get_all_arr($client_id)?:[], 'name', 'value'));
//
//		if(!$this->app->get('settings.timezone'))
//			$this->app->set('settings.timezone', $this->app->get('default_timezone'));
//
//		date_default_timezone_set($this->app->get('settings.timezone'));
//	}
}