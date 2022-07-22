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
			$this->connector->sql("insert into otp (number) values ({$number});");
			return true;
		}

		return 'remote connection error';
	}

}