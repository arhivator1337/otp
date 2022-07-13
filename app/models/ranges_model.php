<?php

class ranges_model extends \models\Model {

	var $db, $model, $cache, $tbl;

	public function __construct() {
		parent::__construct();

		$this->tbl_ranges = 'otp_ranges';
		$this->tbl_otp_number_lists = 'otp_number_lists';
		$this->tbl_otp_number_list_groups = 'otp_number_list_groups';
		$this->model_number_lists = new \DB\SQL\Mapper($this->db, $this->tbl_otp_number_lists);
		$this->model_number_list_groups = new \DB\SQL\Mapper($this->db, $this->tbl_otp_number_list_groups);
		$this->model = new \DB\SQL\Mapper($this->db, $this->tbl_ranges);
	}

	function get_ranges($status = null, $country_id = null, $partner_id = null) {
		$ranges = $this->query_gen("select * from {$this->tbl_ranges} %where%", ['status = :status' => $status, 'country_id IN (:country_id)' => $country_id, 'partner_id = :partner_id' => $partner_id]);

		for ($i = 0; $i < count($ranges); $i++) {
			$ranges[$i]['short_code'] = substr($ranges[$i]['start'], 0, 3);
			$ranges[$i]['partner'] = $this->app->get('partners.' . $ranges[$i]['partner_id']);
		}
		return $ranges;
	}

	function get_list_numbers($group_id = null, $country_id = null) {
		return $this->query_gen("select * from {$this->tbl_otp_number_lists} %where%", ['group_id = :group_id' => $group_id, 'country_id IN (:country_id)' => $country_id]);
	}

	function get_ranges_new($status = null, $country_id = null, $partner_id = null) {
		return $this->query_gen("select * from {$this->tbl_ranges} %where%", ['status = :status' => $status, 'country_id IN (:country_id)' => $country_id, 'partner_id = :partner_id' => $partner_id]);
	}

	function get_number_list_groups($status = null, $country_id = null, $partner_id = null) {
		return $this->query_gen("select * from {$this->tbl_otp_number_list_groups} %where%", ['status = :status' => $status, 'country_id IN (:country_id)' => $country_id, 'partner_id = :partner_id' => $partner_id]);
	}

	function get_stats_lists() {
		return $this->db->exec("SELECT group_id, COUNT(*) as total, status from {$this->tbl_otp_number_lists} GROUP by group_id, status");
	}

	function get_number_list($group_status = null, $country_id = null, $partner_id = null, $number_status = null, $except_country_id = null, $group_id = null) {
		return $this->query_gen("select *, lists.id as number_id, gr.id as range_id from {$this->tbl_otp_number_lists} as lists join {$this->tbl_otp_number_list_groups} as gr on gr.id = lists.group_id  %where%", ['gr.status = :group_status' => $group_status, 'lists.status = :number_status' => $number_status, 'country_id IN (:country_id)' => $country_id, 'country_id NOT IN (:country_id)' => $except_country_id, 'partner_id = :partner_id' => $partner_id, 'gr.id = :group_id' => $group_id]);
	}

	function get_random_range($status = null, $country_id = null, $partner_id = null) {
		return $this->query_gen("select * from {$this->tbl_ranges} %where% ", ['status = :status' => $status, 'country_id = :country_id' => $country_id, 'partner_id = :partner_id' => $partner_id]);
	}

	function update_number_from_list(int $id, int $status) {
		$this->db->exec("UPDATE {$this->tbl_otp_number_lists} SET status = :status where id = :id", [':status' => $status, ':id' => $id]);
	}

	function update_number_from_list_by_number(int $number, int $group_id, int $status) {
		$this->db->exec("UPDATE {$this->tbl_otp_number_lists} SET status = :status where number = :number and group_id = :group_id ", [':status' => $status, ':number' => $number, ':group_id' => $group_id]);
	}

	function list_add_numbers($numbers, $group_id, $country_id) {
		array_walk($numbers, function (&$v, $k) use ($group_id, $country_id) {
			$v = ['group_id' => $group_id,  'number' => (int) $v, 'status' => 1, 'country_id' => $country_id];
		});

		$this->insert_batch_pdo($this->tbl_otp_number_lists, ['group_id', 'number', 'status', 'country_id'], $numbers);
	}
}