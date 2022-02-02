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

	public function get_all_stats($limit = 100, $page = false, $params = []) {

/*
		$pdo_params = [':limit' => $limit];

		$sql_params = [];
		if (($partner_id = validate::filter('int_no_zero', $app->get('GET.partner_id'))) ) {
			$pdo_params[':partner_id'] = $partner_id;
			$sql_params[] = 'ran.partner_id  = :partner_id';
		}

		$str_params = '';
		if(!empty($sql_params))
			$str_params = ' where ' .implode(' and ', $sql_params);
		*/

		$_offset = '';
		$pdo_params = [':limit' => $limit];

		if($page) {
			$_offset = ' OFFSET :offset';
			$pdo_params[':offset'] = $limit*$page;
		}

		$_params = [];
//		if($params['partner_id']) {
//			$_params.= " and ran.partner_id =:partner_id " ;
//			$pdo_params[':partner_id'] = $params['partner_id'];
//		}

//		if($params['task']) {
//			//$_params.= " and t.id IN (:task) "; //$pdo_params[':task'] = implode(',', $params['task']);
//			$_params .= " and t.id IN (" . implode(",", $params['task']) . ")";
//		}
//
		if($params['partner_id'])
			$_params[] = 'ran.partner_id IN (' . implode(',', $params['partner_id']) . ')';
//
//		if($params['status'])
//			$_params.= " and d.dial_status IN('" . implode("','", $params['status']) . "')" ;

		if($params['date_start']) {
			$_params[] = " n.date >= :date_start ";
			$pdo_params[':date_start'] = $params['date_start'];
		}

		if($params['date_finish']) {
			$_params[] = " n.date <= :date_finish ";
			$pdo_params[':date_finish'] = $params['date_finish'];
		}

		if(!empty($_params))
			$str_params = " where " . implode(' and ', $_params);

//		if($params['duration'] && $params['comparison_key']) {
//			$_params.= " and d.duration {$params['comparison_key']} :duration ";
//			$pdo_params[':duration'] = $params['duration'];
//		}

		$sql = "SELECT *, req.date as req_date, n.date as origin_date from {$this->tbl_numbers} as n left join {$this->tbl_number_requests} as req on req.number_id = n.id left join {$this->tbl_ranges} as ran on ran.id = n.range_id {$str_params} order by n.id desc, req.id desc limit :limit";

//		echo '<pre>';
//		print_r($sql);
//		echo '</pre>';
//		echo '<pre>';
//		print_r($pdo_params);
//		echo '</pre>';
//		die;
		return $this->db->exec($sql, $pdo_params);
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