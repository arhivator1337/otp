<?php

class numbers_model extends \models\Model {

	var $db, $model, $cache, $tbl_numbers, $tbl_number_requests, $tbl_ranges;

	public function __construct() {
		parent::__construct();

		$this->tbl_numbers = 'otp_numbers';
		$this->tbl_number_requests = 'otp_number_requests';
		$this->tbl_ranges = 'otp_ranges';
		$this->model = new \DB\SQL\Mapper($this->db, $this->tbl_numbers);
	}

	public function get_all_stats($params = []) {
		$_params = [];
		if($params['unique_numbers'])
			$_params['group_by'] = 'n.number';

		return $this->query_gen("SELECT *, req.date as req_date, n.date as origin_date from {$this->tbl_numbers} as n join {$this->tbl_number_requests} as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id %where% %group_by% order by n.id desc, req.id desc limit :limit",
			['limit' => $params['limit'], 'ran.partner_id IN (:partner_id)' => $params['partner_id'], 'date >= :date_start' => $params['date_start'], 'date <= :date_finish' => $params['date_finish']]  + $_params,
			0
		);
	}

	public function insert_num_request($number_id, $type = 0) {
		return $this->db->exec("insert into {$this->tbl_number_requests} set number_id = :number_id, type =:type, date = :time", [':number_id' => $number_id, ':time' =>  time(), ':type' => $type]);
	}
	public function get_status($number, $time) {
		return $this->db->exec("select * from {$this->tbl_numbers} where number = :number and date >= :time order by date asc limit 1", [':number' => $number, ':time' => $time]);
	}

	public function save_number($number, $range_id, $type, $params = []) {
		$this->model->number = $number;
		$this->model->date = time();
		$this->model->status = 0;
		$this->model->range_id = $range_id;
		$this->model->type = $type;
		$this->model->save();
		return $this->model->_id;
	}

}