<? /*
 api for calls
 */

namespace controllers\system;

use helpers\l10n;
use helpers\validate;

class name_generator {

	function __construct() {
//		parent::__constuct();
		$this->app = \Base::instance();
		$this->names = include 'data/names.php';
		$this->nicks = include 'data/nicknames.php';
	}

//	function get_names(\Base $app, $params) {
//		if($params['param1'] == 'no_ru') {
//			unset($this->names['russia'], $this->names['russia_ru']);
//		}
//		if(isset($params['param1']) && in_array($params['param1'], array_keys($this->names)))
//			$tier = $params['param1'];
//		else
//			$tier = 'all';
//
//		if(in_array($params['param2'], ['male', 'female', 'all']))
//			$gender = $params['param2'];
//		else
//			$gender = 'all';
//
//		$limit = validate::filter('int', $params['param3']) ?: 1000;
//
//		echo "<b>tier:</b> {$tier}<br>";
//
//		echo '<br>';
//
//		$names = $this->generate_name($tier, $gender, $limit);
//		for ($i = 0; $i < count($names); $i++)
//			echo $names[$i] . '<br>';
//	}

	public function generate_name($tier, $gender = 'all', $limit = 1) {
		$_gender = $gender;
		$_tier = $tier;
		$data = [];

		$genders = ['male', 'female'];
		if(isset($this->names[$tier]) or $tier == 'all') {
			if($tier == 'all') {
				foreach (array_keys($this->names) as $val ) {
					$_data = $this->check_tier($val);
					$data[array_keys($_data)[0]] = array_shift($_data);
				}
			}
			else
				$data = $this->check_tier($tier);

			for ($i = 0; $i < $limit; $i++) {
				if($_tier == 'all')
					$tier = array_rand($data, 1);

				if($_gender == 'all')
					$gender = $genders[mt_rand(0, 1)];
				$_name =
					$this->names[$tier][$gender][mt_rand(0, $data[$tier][$gender . '_count'])] . ' ' .
					($data[$tier]['lastname_prefix'] ? $this->names[$tier]['lastname_prefix'][mt_rand(0, $data[$tier]['lastname_prefix'] )] : '');

				$_name = $this->rand(1, $_name, l10n::unaccent($_name));

					if(!empty($this->names[$tier]['lastname']))
						$_lastname = $this->names[$tier]['lastname'][mt_rand(0, $data[$tier]['lastname_count'] )];
					else
						$_lastname = $this->names[$tier]['lastname_' . $gender][mt_rand(0, $data[$tier]["lastname_{$gender}_count"] )];
					//. ' ' . $gender . ' : '.$tier;

				$_lastname = $this->rand(1, $_lastname, l10n::unaccent($_lastname));

				$name[] = $this->randomize_case($_name . $_lastname);
			}

			return $name;
		}
	}

	public function rand($max = 1, $str, $executed) {
		if(mt_rand(0, $max) == $max)
			return $executed;

		return $str;
	}

	public function generate_user_agents($limit = 1) {
//		$limit = validate::filter('int', $params['param1']) ?: 10000;
		$ua = include 'data/user_agents.php';
		$new = $data = [];

		$_limit = $limit < 100 ? 100 : $limit;

		for ($i = 0; $i < count($ua); $i++) {
			$key = key($ua[$i]);
			$data[] = array_fill(0, $_limit * $key / 100, $ua[$i][$key]);
		}

		for ($i = 0; $i < count($data); $i++) {
			foreach ($data[$i] as $v) {
				$new[] = $v;
			}
		}
		shuffle($new);

		if($limit == 1)
			return $new[0];
		else
			return $new;
	}

//	public function generate_nicknames(\Base $app, $params) {
//		$limit = validate::filter('int', $params['param1']) ?: 1000;
//		$nicks = $this->generate_nickname($limit);
//		for ($i = 0; $i < count($nicks); $i++)
//			echo $nicks[$i] . '<br>';
//
//	}

	public function generate_nickname($limit = 1, $name_in_nick = false)  {
		for ($i = 0; $i < $limit; $i++) {
//			if($limit == 1 && $_part1 && mt_rand(0, 1) == 1)
			$part1 = $this->nicks['part1'][mt_rand(0, count($this->nicks['part1'])-1)];
			$part2 = $this->nicks['part2'][mt_rand(0, count($this->nicks['part2'])-1)];

			$glue = $this->glue_generator();
			$glue2 = $this->glue_generator(true);
			if($glue == $glue2)
				$glue2 = $this->glue_generator(true);

			if($limit == 1 && $name_in_nick && mt_rand(0, 1) == 1)
				$first = l10n::unaccent(l10n::translit($name_in_nick));
			else
				$first = $part1[mt_rand(0, count($part1)-1)];

			$nick = $first. $glue . $part2[mt_rand(0, count($part2)-1)] . $glue2;
			$nick = $this->randomize_case($nick);

			$ret[] = str_replace(['(', ')', ' '], '', $nick);
		}
		return $ret;
	}

	private function randomize_case($text) {
		$case_rand = mt_rand(0, 5);

		if($case_rand == 1)
			$text = strtolower($text);
		elseif($case_rand == 2)
			$text = ucwords(strtolower($text));
		elseif($case_rand == 3)
			$text = ucfirst(strtolower($text));
		elseif($case_rand == 4)
			$text = lcfirst($text);
		//5 do nothing

		return $text;
	}

	private function glue_generator($end = false) {
		$glues = ['.', '.', '', '', 'number_1', 'number_2'];
		$glues2 = ['.', 'number_1', 'number_2', 'number_3'];

		if($end)
			$glues = $glues2;

		$glue = $glues[mt_rand(0, count($glues)-1)];

		if($glue == 'number_1')
			$glue = mt_rand(0, 9);

		if($glue == 'number_2')
			$glue = mt_rand(10, 99);

		if($glue == 'number_3')
			$glue = mt_rand(99, 999);

		if($end && ($glue == '.'))
			$glue.= mt_rand(9, 99);

		return $glue;
	}

	private function check_tier($tier) {
		if (empty($data[$tier]['female_count']))
			$data[$tier]['female_count'] = count($this->names[$tier]['female']) - 1;
		if (empty($data[$tier]['male_count']))
			$data[$tier]['male_count'] = count($this->names[$tier]['male']) - 1;
		if (empty($data[$tier]['lastname_count']) && !empty($this->names[$tier]['lastname']))
			$data[$tier]['lastname_count'] = count($this->names[$tier]['lastname']) - 1;

		if (empty($data[$tier]['lastname_male_count']) && is_array($this->names[$tier]['lastname_male']))
			$data[$tier]['lastname_male_count'] = count($this->names[$tier]['lastname_male']) - 1;

		if (empty($data[$tier]['lastname_female_count']) && is_array($this->names[$tier]['lastname_female']))
			$data[$tier]['lastname_female_count'] = count($this->names[$tier]['lastname_female']) - 1;

		return $data;
	}

}