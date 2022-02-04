<?php
namespace helpers;

class html {

	public static $log = [];
	public static $file_download_result = '';

	public static function prepare_urls($array, $local = true) {
		if (empty($array))
			return '';

		foreach ($array as $key => $val)
			$ret_array[] = "{$key}={$val}";

		$cur_url = $local ? \Base::instance()->get('current_url') : '';

		return $cur_url. '?' . implode('&', $ret_array);
	}

	public static function url($url, $title = false, $params = []) {
		$_params = '';

		foreach ($params as $key => $val)
			$_params.= "{$key}=\"{$val}\" ";

		$url = $url == '#' ? '#' : l10n::default_url() . $url;

		return $title ? "<a href=\"{$url}\" {$_params}>{$title}</a>" : $url;
	}

	public static function ajax_error($error) {
		echo json_encode(['error' => 1, 'error_message' => $error]);
		die;
	}

	public static function to_json($array, $profiler = []) {
		for ($i = 0; $i < count($array); $i++) {
			$arr[] = $array[$i];
		}
		return json_encode(['data' => $arr, 'profiler' => $profiler]);
	}


	static function curl($url, $settings = array (), $post_fields = array()) {
		$app = \Base::instance();
		/*$headers = array(
			'Content-Type: application/json;odata=verbose',
		);
		*/
		if (isset($settings['token']))
			$headers[] = '' . $settings['token'];

		$ch = curl_init($url);

		$cookie = $settings['cookie'] ? $settings['cookie'] : $app->get('cookie');

		if (!file_exists($cookie) || !is_writable($cookie))
			die('Cookie file missing or not writable.');

		//curl_setopt($ch, CURLOPT_REFERER, $settings['referer']);
		curl_setopt($ch, CURLOPT_USERAGENT, $settings['user_agent'] ? $settings['user_agent'] : 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);


		if(!empty($post_fields)) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($ch, CURLOPT_RED, $headers);

		if($settings['timeout'])
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $settings['timeout']);

		if($settings['proxy_ip']) {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL , 1);
			curl_setopt($ch, CURLOPT_PROXY, $settings['proxy_ip']);
			curl_setopt($ch, CURLOPT_PROXYPORT, $settings['proxy_port']);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $settings['proxy_user'] . ':' . $settings['proxy_pass']);
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		//https
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


		$result = curl_exec($ch);
//		$this->log[] = curl_getinfo($ch);
//		$this->log['error'] = curl_error($ch);
//		$this->log[] = $result;

		curl_close($ch);

		return $result;
	}

	public static function json_encode(array $arr, $sequential_keys = false, $quotes = false, $beautiful_json = false, $js = true) {
		$output = ($js == true) ? "{" : '';
		$count = 0;
		foreach ($arr as $key => $value) {
			if(self::isAssoc($arr) || (!self::isAssoc($arr) && $sequential_keys == true ))
				$output .= ($quotes ? '"' : '') . $key . ($quotes ? '"' : '') . ' : ';

			if(is_array($value))
				$output .= self::json_encode($value, $sequential_keys, $quotes, $beautiful_json);
			elseif(is_bool($value))
				$output .= ($value ? 'true' : 'false');
			elseif(is_numeric($value))
				$output .= $value;
			else
				$output .= ($quotes || $beautiful_json ? '"' : '') . $value . ($quotes || $beautiful_json ? '"' : '');

			if (++$count < count($arr))
				$output .= ', ';
		}
		$output.= ($js == true) ? "}" : '';
		return $output;
	}

	public static function check_proxy_real_ip($proxy) {
//		echo $d = self::curl2("https://2ip.ru/geoip", ['user_agent' => 'curl', 'referer' => 'https://news2.ru/', 'timeout' => 5, 'proxy_ip' => $proxy['ip'], 'proxy_port' => $proxy['port'], 'proxy_user' => $proxy['login'], 'proxy_pass' => $proxy['pass']]);
		$settings = ['user_agent' => 'curl', 'timeout' => 1, 'proxy_ip' => $proxy['ip'], 'proxy_port' => $proxy['port'], 'proxy_login' => $proxy['login'], 'proxy_pass' => $proxy['pass']];
//		$real_ip = self::curl2("https://2ip.ru/", $settings);
//		echo $real_ip;
//		echo '<pre>';
//		print_r(html::$log);
//		echo '</pre>';
//		if($real_ip = validate::filter('ip', trim($real_ip))) {
//			echo $real_ip2 = self::curl2($url = "https://ipapi.co/{$real_ip}/country/", $settings);

//		}

		$ip_data = self::curl2($url = "http://ip-api.com/json?fields=status,country,countryCode,hosting,mobile,proxy,query", $settings);
		if(strpos($ip_data, '{') !== false) {
			$data = json_decode($ip_data, true);
			return $data;
		}
		return [];
	}

	static function curl_del($url, $settings = [], $post_fields = []) {
		$app = \Base::instance();
		/*$headers = array(
			'Content-Type: application/json;odata=verbose',
		);
		*/
		if (isset($settings['token']))
			$headers[] = '' . $settings['token'];

		$ch = curl_init($url);

		$cookie = $settings['cookie'] ? $settings['cookie'] : $app->get('cookie');

		if (!file_exists($cookie) || !is_writable($cookie))
			die('Cookie file missing or not writable.');


		if($settings['proxy_ip']) {
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($ch, CURLOPT_PROXY, $settings['proxy_ip'] . ':' . $settings['proxy_port']);
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL , 1);
//			curl_setopt($ch, CURLOPT_PROXYPORT, $settings['proxy_port']);
//			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $settings['proxy_login'] . ':' . $settings['proxy_pass']);
		}

//		if($settings['file']) {
//			curl_setopt($ch, CURLOPT_FILE, $settings['file']);
//		}

		if($settings['download_into_var']) {
			self::$file_download_result = '';
			$callback = function ($ch, $str) {
				self::$file_download_result.= $str;
				return strlen($str);
			};
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);
		}

		if($settings['referer'])
			curl_setopt($ch, CURLOPT_REFERER, $settings['referer']);

		curl_setopt($ch, CURLOPT_USERAGENT, $settings['user_agent'] ? $settings['user_agent'] : 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');

		if($settings['cookie_array']) {
			$_cookies = [];
			foreach($settings['cookie_array'] as $key => $value)
				$_cookies[] = "{$key}={$value}";
			echo implode('; ', $_cookies);
			curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $_cookies));
		}
		else {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		}

		if(!empty($post_fields)) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		if(!isset($settings['redirect']))
			$settings['redirect'] = true;

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $settings['redirect']);

		//https
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);



		$result = curl_exec($ch);
//		var_dump(curl_getinfo($ch));
//		var_dump(curl_error($ch));
//		self::$log[] = curl_getinfo($ch);
//		self::$log['error'] = curl_error($ch);
//		self::$log[] = $result;

		curl_close($ch);

//		if($settings['file'])
//			curl_close($settings['file']);

		if($settings['download_into_var'])
			self::$file_download_result;

		return $result;
	}

	static function curl2($url, $settings = [], $post_fields = []) {
		$app = \Base::instance();
		/*$headers = array(
			'Content-Type: application/json;odata=verbose',
		);
		*/
		if (isset($settings['token']))
			$headers[] = '' . $settings['token'];

		$ch = curl_init($url);

		$cookie = $settings['cookie'] ? $settings['cookie'] : $app->get('cookie');

		if (!file_exists($cookie) || !is_writable($cookie))
			die('Cookie file missing or not writable.');


		if($settings['proxy_ip']) {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL , 1);
			curl_setopt($ch, CURLOPT_PROXY, $settings['proxy_ip']);// . ':' . $settings['proxy_port']);
			curl_setopt($ch, CURLOPT_PROXYPORT, $settings['proxy_port']);
			if($settings['proxy_ip'] > 15) {
				curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
//				curl_setopt($ch, CURLOPT_PROXYPORT, $settings['proxy_port']);
			}

			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $settings['proxy_login'] . ':' . $settings['proxy_pass']);
		}

//		if($settings['file']) {
//			curl_setopt($ch, CURLOPT_FILE, $settings['file']);
//		}

		if($settings['download_into_var']) {
			self::$file_download_result = '';
			$callback = function ($ch, $str) {
				self::$file_download_result.= $str;
				return strlen($str);
			};
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);
		}

		if($settings['referer'])
			curl_setopt($ch, CURLOPT_REFERER, $settings['referer']);

		curl_setopt($ch, CURLOPT_USERAGENT, $settings['user_agent'] ? $settings['user_agent'] : 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');

		if($settings['cookie_array']) {
			$_cookies = [];
			foreach($settings['cookie_array'] as $key => $value)
				$_cookies[] = "{$key}={$value}";
			echo implode('; ', $_cookies);
			curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $_cookies));
		}
		else {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		}

		if(!empty($post_fields)) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, !empty($settings['headers']) ? true : false);
		if($settings['headers'])
			curl_setopt($ch, CURLOPT_HTTPHEADER, $settings['headers']);

		//curl_setopt($ch, CURLOPT_RED, $headers);

		if(!isset($settings['redirect']))
			$settings['redirect'] = true;

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,  $settings['redirect']);

		//https
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


		$result = curl_exec($ch);
		self::$log[] = curl_getinfo($ch);
		self::$log['error'] = curl_error($ch);
		self::$log[] = $result;

		curl_close($ch);

//		if($settings['file'])
//			curl_close($settings['file']);

		if($settings['download_into_var'])
			self::$file_download_result;

		return $result;
	}

	public function proxy_check() {
		$ip = $this->curl_wrapper('https://2ip.ru', ['user_agent' => 'curl']);
//		if($this->app->get('proxy.ip') != trim($ip))
//			$this->message->add('system', __METHOD__, 'proxy error. my ip is' . $ip . ' instead of ' . $this->app->get('proxy.ip'), true);
	}

	public static function isAssoc(array $arr) {
		if (array() === $arr) return false;
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}