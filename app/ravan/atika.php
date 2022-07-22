<?php
//Atikaya son of Ravana

namespace ravan;

class atika extends prefab {
	protected $pdb = false;
	protected $goaf_connection_flag = false;
	protected $connector = null;
	protected $free_lines, $connectors_settings, $color_ids = [];

	function __construct($client_id) {
		$this->client_id = $client_id;
		$this->app = app::instance();
	}

	public function connect($pdb_host, $pdb_user, $pdb_pass, $pdb_name, $pdb_port = false) {
		try {
			if(!$pdb_port)
				$pdb_port = $this->app->cfg['pdb_port'];
			$this->goaf_connection_flag = true;
			return $this->connector = new db($connect = "pgsql:dbname={$pdb_name};port={$pdb_port};host={$pdb_host}", $pdb_user, $pdb_pass);
		}
		catch (\PDOException $e) {
			out('db connection error:' . $connect . ";user:{$pdb_user};pass:{$pdb_pass}", C_BG_RED);
			//$this->pdb->sql = new class{ function sql($arg){return null;}};
		}
	}

	public function send_number(int $number) {
		if($this->connector != null && method_exists($this->connector, 'sql')) {
			$this->connector->sql('insert into otp (number) values (:number);', [':number' => $number]);
			return true;
		}

		return 'remote connection error';
	}

//	public function get_connectors_settings() {
//		if(empty($this->connectors_settings))
//			return $this->connectors_settings = helper::map_id($this->app->db->sql("SELECT *, cc.id as color_id from goaf_connector_colors as cc join goaf_connectors as c on c.id = cc.connector_id where client_id = {$this->client_id}"), 'color_id');
//		else
//			return $this->connectors_settings;
//	}
//
//	public function get_colors(&$connector) {
//		if(method_exists($connector, 'sql')) {
//			$colors = $connector->sql('select * from color');
//			return helper::map_id($colors, 'name');
//		}
//		return false;
//	}
//
//	private function get_all_free_sim(&$connector) {
//		if(method_exists($connector, 'sql'))
//			return helper::map_id($connector->sql('select c.name, count(*)  from sip_conf as s join color as c on c.id = color_id where s.status = 10 group by s.color_id, c.name'), 'name');
//		else return [];
//	}
//
//	public function get_free_sim_by_color($color_name, &$connector) {
//		$sims = $this->get_all_free_sim($connector);
//		if((app::instance())->cfg['debug'] >= 3) {
//			foreach ($sims as $id => $color)
//				$str .= "{$id}:{$color['count']},";
//			out('free sims: ' . $str, C_BG_GREEN_LIGHT);
//		}
//
//		if(is_string($color_name))
//			return isset($sims[$color_name]['count'])? $sims[$color_name]['count'] : 0;
//		elseif(is_array($color_name)) {
//			foreach ($color_name as $color)
//				$free_sims[$color] = $sims[$color]['count'] ?: 0;
//		}
//
//		if($this->cfg['debug'] >= 3)
//			out_h('$free_sims', $free_sims);
//		return $free_sims;
//	}
//
//	public function get_free_lines_by_atika_id($color_id) {
//		if((app::instance())->cfg['debug'] >= 3)
//			out('get_free_lines_by_atika_id: ' . $color_id, C_RED);
//		$goaf_settings = $this->get_connectors_settings();
//
//		$goaf_sett_color_name = helper::map_id($goaf_settings, 'color_name');
//		$goaf_grouped_by_db = helper::group_by_id($goaf_settings, 'db_name');
//
//		if(($pdb_data = $goaf_settings[$color_id]) && !isset($this->free_lines[$color_id])) {
//			if(!$this->connectors[$pdb_data['db_name']]) {
//				$this->connectors[$pdb_data['db_name']] = $this->connect($pdb_data['server_ip'], $pdb_data['db_user'], $pdb_data['db_pass'], $pdb_data['db_name']);
//				\ravan\out('goaf connected', C_BG_YELLOW);
//			} else {
//				if((app::instance())->cfg['debug'] >= 3)
//					\ravan\out('connection is present for '. $pdb_data['db_name'], C_BG_YELLOW);
//			}
//
//			$colors = array_column($goaf_grouped_by_db[$pdb_data['db_name']], 'color_name');
//			$free_lines = $this->get_free_sim_by_color($colors, $this->connectors[$pdb_data['db_name']]);
//
//			foreach ($free_lines as $name => $count)
//				$this->free_lines[ $goaf_sett_color_name[$name]['color_id'] ] = $count;
//		}
//
//		$this->save_color_id($color_id);
//		return $this->free_lines[$color_id];
//	}
//
//	public function occupy_line($color_id) {
//		if($this->free_lines[$color_id] > 0)
//			$this->free_lines[$color_id]--;
//
//		if($this->app->cfg['debug'] >= 2)
//			out('count goaf lines:' . $color_id . ' ' . $this->free_lines[$color_id]);
//	}
//
//	public function clear_all_lines() {
//		$this->free_lines = [];
//	}
//
//	public function count_all_lines() {
//		$sum = 0;
//		foreach ($this->color_ids as $ids)
//			$sum+= intval($this->free_lines[$ids]);
//		return $sum;
//	}
//
//	public function save_color_id($color_id) {
//		if(!in_array($color_id, $this->color_ids))
//			$this->color_ids[] = $color_id;
//	}
//
//	public function reset_connection() {
//		$this->goaf_connection_flag = false;
//		$this->connectors_settings = [];
//		$this->free_lines = [];
//
//		foreach ($this->connectors as $id => $con) {
//			$con->reconnect();
//			out('drop connection ' . $id, C_BG_YELLOW_LIGHT);
//			unset($this->connectors[$id]);
//		}
//	}
//
//	public function check_connected_flag() {
//		if($this->app->cfg['debug'] >= 3)
//			out('goaf_connection_flag:' . $this->goaf_connection_flag, C_RED);
//		return $this->goaf_connection_flag;
//	}

}