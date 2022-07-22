<?
namespace ravan;

include_once 'prefab.php';

class app extends prefab {
	var $dir, $cfg;

	function __construct($dir = false, $connect_sql = false) {
		$this->app_dir = $dir ? $dir . 'app/' : __DIR__ . '/../' ;
		$this->base_dir = $dir ? $dir . '/' : __DIR__ . '/../../';
		include_once $this->base_dir . '/vendor/autoload.php';
		$this->config();
		spl_autoload_register([$this,'autoload']);
		if($connect_sql)
			$this->db = new db("mysql:dbname={$this->cfg['db_name']};host={$this->cfg['db_host']}", $this->cfg['db_user'], $this->cfg['db_pass']);
	}

	function config($name = false) {
		$this->cfg =  file_exists($d_path = $this->base_dir . 'demon.ini') ? parse_ini_file($d_path, true) : [];
		$cfg1 = file_exists($m_path = $this->base_dir . 'marketing') ? parse_ini_file($m_path, true) : [];
		$cfg2 = parse_ini_file($this->base_dir . ($name ?: 'env.ini'), true);

		$this->cfg = array_replace_recursive($this->cfg, $cfg1, $cfg2);
	}

	protected function autoload($class) {
		if(is_file($file = $this->app_dir . str_replace('\\', '/', $class) . '.php'))
			return require $file;
	}
}

return app::instance($base_dir ?: false, $connect_sql?: false);