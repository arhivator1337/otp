<?php
class users_model extends \models\Model {

	var $db, $model, $cache;

	public function __construct() {
		$this->table = 'users';
		$this->db = \Base::instance()->get('db');
		$this->model = new \DB\SQL\Mapper($this->db, $this->table);
	}

	public function get_all() {
		return $this->model->find(NULL, ['order by client_id, id']);
	}

	public function get($id) {
		return $this->model->findone(['id = :id', ':id' => $id]);
	}

	public function flush_cache() {
		$this->cache->clear(__CLASS__ . ':get_all');
	}

	public function delete($id) {
		if(($this->get($id))->id)
			return $this->model->erase(['id = :id', ':id' => $id]);
	}


}

