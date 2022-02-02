<?php
namespace helpers;

class utils
{
	public static function save_csv($header_fields, $array, $filename) {
		$output = fopen("php://output", 'w') or die("Can't open php://output");

		header("Content-Type:application/csv");
		header("Content-Disposition:attachment;filename={$filename}.csv");

		$app = \Base::instance();

		fputcsv($output, $header_fields, $app->get('csv_delimiter'));

		foreach ($array as $val)
			fputcsv($output, $val, $app->get('csv_delimiter'));

		fclose($output) or die("Can't close php://output");
	}

	public static function save_csv2($array, $filename) {
		$output = fopen("php://output", 'w') or die("Can't open php://output");

		header("Content-Type:application/csv");
		header("Content-Disposition:attachment;filename={$filename}.csv");

		$app = \Base::instance();

		foreach ($array as $val)
			fputcsv($output, $val, $app->get('csv_delimiter'));

		fclose($output) or die("Can't close php://output");
	}

	public static function save_file($file, $content) {
		return file_put_contents($file, $content);
	}

	public static function load_file($file) {
		return file_get_contents($file);
	}

	public static function salt_sha2($size = 256) {
		$allSizes = array(256, 384, 512);
		if (!in_array($size, $allSizes))
			throw new \Exception("Hash size must be one of: " . implode(", ", $allSizes));

		return hash("sha{$size}", self::randBytes(512), false);
	}

	/**
	 * Generate secure random bytes
	 * @param  integer $length
	 * @return binary
	 */

	public static function randBytes($length = 16)
	{
		// Try to use native secure random
		if (function_exists('random_bytes')) {
			return random_bytes($length);
		}

		// Fall back to OpenSSL cryptography extension if available
		if (function_exists("openssl_random_pseudo_bytes")) {
			$strong = false;
			$rnd = openssl_random_pseudo_bytes($length, $strong);
			if ($strong === true) {
				return $rnd;
			}
		}

		// Use SHA256 of mt_rand if OpenSSL is not available
		$rnd = "";
		for ($i = 0; $i < $length; $i++) {
			$sha = hash("sha256", mt_rand());
			$char = mt_rand(0, 30);
			$rnd .= chr(hexdec($sha[$char] . $sha[$char + 1]));
		}
		return (binary) $rnd;
	}


	public static function universal_phone_code_searcher($key, &$array) {
		while (strlen($key) > 0) {
			if (array_key_exists($key, $array)) {
				return $array[$key];
			}
			$key = substr($key, 0, -1);
		}
		return false;
	}

}