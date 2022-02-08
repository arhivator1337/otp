<?php
class clients_model extends \models\Model {

	var $db, $model, $cache;

	public function __construct() {
		$this->table = 'clients';
		$this->db = \Base::instance()->get('db');
		$this->model = new \DB\SQL\Mapper($this->db, $this->table);
	}

	public function get_all() {
		return $this->model->find(NULL, ['order by id']);
	}

	public function get_all_arr() {
		return $this->to_array($this->model->find(), $this->model, true);
	}

	public function get($id) {
		return $this->model->load(['id = :id', ':id' => $id]);
	}

	public function delete($id) {
		if(($this->get($id))->id)
			return $this->model->erase(['id = :id', ':id' => $id]);
	}

	public function stats($date_from, $date_to, $client_id = false) {
		$pdo_params = [':date_from' => $date_from, ':date_to' => $date_to];
		$_params[] = " date >= :date_from and date <= :date_to ";

		if($client_id !== false) {
			$_params[] = ' client_id = :client_id';
			$pdo_params[':client_id'] = $client_id;
		}
		$sql_params = implode(' and ', $_params);
		return $this->db->exec("SELECT round(sum(bill_sec)/60, 0) as minutes, client_id from dial_history  where dial_history.bill_sec > 0 and dial_status = 'ANSWER' and {$sql_params} group by client_id", $pdo_params);
	}

}

