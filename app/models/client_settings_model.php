<?php
class client_settings_model extends \models\Model {

	var $db, $model, $cache;

	public function __construct() {
		parent::__construct();
		$this->table = 'client_settings';
		$this->model = new \DB\SQL\Mapper($this->db, $this->table);
	}

	public function get_by_id($id, $client_id) {
		return $this->model->load(['id = :id and client_id = :client_id', ':id' => $id, ':client_id' => $client_id]);

	}
	public function get_all($client_id) {
		return $this->model->find(NULL, ['order by id']);
	}

	public function get_all_arr($client_id) {
		return $this->to_array($this->model->find(['client_id=?', $client_id], ['order' => 'name']), $this->model, true);
	}

	public function delete($id, $client_id) {
		if(($model = $this->get_by_id($id, $client_id))->id)
			return $model->erase(['id = :id', ':id' => $id]);
	}

	public function delet_all($client_id) {
		return $this->model->erase(['client_id = :client_id', ':client_id' => $client_id]);
	}
}