<?php

class numbers_data_model extends \models\Model {

	var $db, $model, $cache, $tbl;

	public function __construct() {
		parent::__construct();

		$this->tbl = 'otp_numbers_data';
		$this->model = new \DB\SQL\Mapper($this->db, $this->tbl);
	}

	public function save_number_data($number_id = null, $proxy = null, $real_ip = null, $country = null, $user_agent = null, $name = null, $nickname = null, $extra = null ) {
		$this->model->number_id = $number_id;
		$this->model->proxy = $proxy;
		$this->model->real_ip = $real_ip;
		$this->model->country = $country;
		$this->model->user_agent = $user_agent;
		$this->model->name = $name;
		$this->model->nickname = $nickname;
		$this->model->extra = $extra;
		$this->model->save();
	}

}