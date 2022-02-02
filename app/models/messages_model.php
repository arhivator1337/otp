<?php
namespace models;

class messages_model extends Model {

	var $db, $model, $cache;

	public function __construct() {
		parent::__construct();
		$this->table = 'messages';
		$this->model = new \DB\SQL\Mapper($this->db, $this->table);
	}

	public function get_all($module = false, $status = false, int $limit = 1000) {
//		$sql_params = [':status' => $status];

		if($module) {
			$params[] = " site = :site";
			$sql_params['site'] = $module;
		}

		if($status) {
			$params[] = " status = :status";
			$sql_params['status'] = $status;
		}

		$params = !empty($params) ?' where ' . implode(' and ', $params) : '';

		return $this->db->exec("select * from {$this->table} {$params} order by id desc limit {$limit}", $sql_params);
	}

	public function add_message($type, $module, $message, $fatal = 0) {
		$this->model->type = $type;
		$this->model->module = $module;
		$this->model->message = $message;
		$this->model->fatal = $fatal;
		$this->model->status = 0;
		$this->model->date = time();
		$this->model->save();
		$this->model->reset();
	}

}




