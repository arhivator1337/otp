<?
namespace ravan;

class log extends app {

	public $log_file;

	public function __construct($log_file = false) {
		$app = app::instance();
		$this->log_file = $log_file ?: $app->cfg['log_dir'] . $app->cfg['log_file'];
	}

	function add2($message) {
		fwrite($this->log_file, $message);
	}

	function add($data, $append = true) {
		return file_put_contents($this->log_file, $data . PHP_EOL,FILE_APPEND);
	}

	function add_custom($file_name, $data, $append = true) {
		return file_put_contents($file_name, $data . PHP_EOL,$append?FILE_APPEND:0);
	}
}
