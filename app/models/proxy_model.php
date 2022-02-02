<?php

class proxy_model extends \models\Model {

	var $db, $model, $cache, $tbl;

	public function __construct() {
		parent::__construct();

		$this->tbl = 'proxy';
		$this->model = new \DB\SQL\Mapper($this->db, $this->tbl);
	}

	function get_proxy($country_id = null, $status = null, $expire = null) {
		return $this->query_gen("select * from {$this->tbl} %where%", ['status = :status' => $status, 'country_id IN (:country_id)' => $country_id, 'expire >= :expire' => $expire]);
	}
}