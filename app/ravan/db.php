<?
namespace ravan;

class db extends prefab {

	private $dsn, $username, $password = '';
	private $options = [];
	protected $pdo = null;

	public function __construct($dsn, $username = '', $password = '', $options = []) {
		$this->dsn = $dsn;
		$this->username = $username;
		$this->password = $password;
		$this->options;
		$this->pdo();
	}

	private function pdo() {
		if (is_null($this->pdo))
			$this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
		return $this->pdo;
	}

	public function reconnect() {
		if((app::instance())->cfg['debug'] == 3)
			out('reconnect ' . $this->dsn);
		$this->pdo = null;
	}

	public function sql($query, $options = false) {
		if((app::instance())->cfg['debug'] == 3)
			out('sql_query: ' . $query, C_GREEN);
		return ($this->exec($query))->fetchAll($options ? $options : \PDO::FETCH_ASSOC);
	}

	function last_insert_id() {
		return $this->pdo()->lastInsertId();
	}

	public function exec($sql, $options = false) {
		$retries = 0;
		while (true)  {
			try  {
				return $this->pdo()->query($sql);
			}
			catch (\PDOException $ex) {
				$this->pdo = NULL;
				if (++$retries > 5) {
					// We've passed our retry limit
					throw $ex;
				}
			}
		}
	}


}