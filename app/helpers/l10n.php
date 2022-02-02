<?php
namespace helpers;
//https://laravel.ru/docs/v5/localization#множественное

class l10n {

	public static $lang_code = false;

	public static $original_lang = [];

	//@todo доработать
	public static function plural($words = [/*1*/'1 комментарий', /*2*/'2 комментария', /*5*/'5 комментариев'], $number) {
		switch (self::$lang_code) {
			case 'ru': {
				//[/*1*/'комментарий', /*2*/'комментария', /*5*/'комментариев']
				$cases = [2, 0, 1, 1, 1, 2];
				return $number . ' ' . $words[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
			}
			case 'en':
				//[/*1*/ 'day', /*2*/'days']
				return $number . ' ' . $words[$number == 1 ? 0 : 1];
			default:
				throw new \Exception('$lang_code is not defined');
		}
	}

	public static function default_url($lang = false) {
		$app = \Base::instance();
		$key = $lang ? $lang : self::$lang_code;

		if(!in_array($key, $app->get('languages')))
			$key = '';
		else {
			if ($app->exists('lang_base_url.' . $key))
				$key = $app->get('lang_base_url.' . $key);
		}
		return $key;
	}

	public static function check_original_lang($key) {
		$app = \Base::instance();
		if(empty(self::$original_lang))
			self::$original_lang = array_merge(['loaded' => true], parse_ini_file($app->get('LOCALES') . \helpers\l10n::$lang_code . '.ini', true, INI_SCANNER_RAW));
		if(isset(self::$original_lang[$key]))
			return true;
		else
			return false;
	}

	public static function single_en($string) {
		if(str_ends_with($string, 's'))
			$string = substr($string, 0, -1);
		return $string;
	}

	//@todo
	public static function format($dict_element, $number) {
		$app = \Base::instance();
		echo $dict_element;
		if($app->exists($dict_element)) {
			preg_match_all('/{(.+?)}(.?)^{|/', $app->get($dict_element), $matches);
			if(!empty($matches[1]))
				var_dump(self::plural($matches[1], $number));
			else
				var_dump($number . ' ' . $app->get($dict_element));
		}
	}

	public static function translit($value) {
		$converter = [
			'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
			'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
			'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
			'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
			'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
			'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
			'э' => 'e',    'ю' => 'yu',   'я' => 'ya',

			'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
			'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
			'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
			'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
			'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
			'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
			'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',
		];

		$value = strtr($value, $converter);
		return $value;
	}
}