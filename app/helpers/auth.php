<?php
namespace helpers;

class auth
{
	const
		GROUP_GUEST = 0,

		GROUP_OBSERVER = 1,
		GROUP_NOC = 2,
		GROUP_ADMIN = 3,
		GROUP_SUPER_OBSERVER = 11,
		GROUP_SUPER_NOC = 12,
		GROUP_SUPER_ADMIN = 13;

		//1 - observer
		//2 - noc
		//3 - admin
		//11 - super obs
		//12 - super noc
		//13 - super adm

	public static function login_post() {
		$app = \Base::instance();

		if ($app->get("SESSION.user.id") > 0)
			return $app->get("SESSION.user");

		$user = new \DB\SQL\Mapper($app->get('db'), 'users');

		$user->load(array("username=?", $app->get("POST.username")));
		if ($user->loaded()) {
			$crypt = \Bcrypt::instance();
			if ($crypt->verify($app->get("POST.password"), $user->password)) {
				$client = \clients_model::instance()->get($user->client_id);
				$userdata = [
					'id' => $user->id,
					'name' => $user->username,
					'group' => $user->group,
					'client_id' => $user->client_id,
					'client_name' => $client['name'],
				];

				$app->set('user', $userdata);
				$_SESSION['user'] = $userdata;

				return $userdata;
			}
		}
		return false;
	}

	public static function get_user_ip($long = false) {
		if (!empty($_SERVER['HTTP_X_REAL_IP']))
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else
			$ip = $_SERVER['REMOTE_ADDR'];

		$longed_ip = ip2long($ip);

		return ($longed_ip !== false && $long === true) ? sprintf('%u', $longed_ip) : $ip;
	}

	public static function logout() {
		$app = \Base::instance();

		$app->clear('user');
		$app->clear('SESSION.user');

		$app->reroute(\helpers\html::url('/user/login'));
		return false;
	}

	public static function require_login($group = self::GROUP_OBSERVER) {
		$app = \Base::instance();

		$user = self::get_user_data();
		if (isset($user['id']) && $user['id'] > 0) {
            if ($app->get("user.group") >= $group) {
	            return true;
            }
            else {
				$app->reroute(\helpers\html::url('/user/no_rights?min_group=' . $group));
                return false;
            }
		} elseif($user === NULL) {
			$reroute = urlencode($app->get("PATH"));

			if (!empty($_GET))
				$reroute = $reroute . urlencode("?" . http_build_query($_GET));
			$app->reroute(\helpers\html::url("/user/login?to=" . $reroute));
			exit;
		}
		else {
			$app->reroute(\helpers\html::url("/user/login"));
		}
		die;
	}

	public static function get_user_data() {
		$app = \Base::instance();

		if($app->get('user.id') > 0)
			return $app->get('user');
		if($app->exists('SESSION.user.id'))
			return $app->set('user', $app->get('SESSION.user'));
	}

	public static function check_right($group_needed) {
		$app = \Base::instance();

		self::get_user_data();

		if($app->get('user.group') >= $group_needed)
			return true;
		else
			return false;
	}

	public static function require_admin() {
		self::require_login(self::GROUP_ADMIN);
	}

	public static function require_super() {
		self::require_login(self::GROUP_SUPER_ADMIN);
	}

	public static function get_user_groups() {
		$groups = (new \ReflectionClass(__CLASS__))->getConstants();
		$new = [];

		foreach (array_flip($groups) as $id => $g)
			$new[$id] = ucfirst(strtolower(str_replace('GROUP_', '', $g)));
		return array_flip($new);
	}

	public static function session_start() {
		if(\Base::instance()->get('CACHE')) {
			new \Session(function ($session) {
				// Suspect session
	//				$logger = new \Log('logs/session.log');
	//				if (($ip = $session->ip()) != $app->get('IP'))
	//					echo ('user changed IP:' . $ip);
	//				else
	//					echo ('user changed browser/device:'. $app->get('AGENT'));

				// The default behaviour destroys the suspicious session.
				return false;
				},
				NULL, $cache = \Cache::instance(\Base::instance()->get('CACHE'))
			);
		} else
			session_start();
	}

	public static function generate_session_id() {
		$rand = mt_rand(1, 1000000);
		$salt = $rand % 2 == 0 ? '-MNMkjhkasdfxaccawefSGwb@@@#' : 'ygsTRSAtwu1awcwcegweawf%$r@h@IUI';
		return md5(microtime(true) . $rand . $salt);
	}
}