<?
function __($dict, $param1 = null, $param2 = null, $param3 = null) {
	$app = Base::instance();

	$key = mb_strtolower($dict);
	$prefix = 'l.';

	if($app->exists($prefix . $dict))
		$key = $prefix . $dict;

	if($param1) {
		return $app->format($app->get($key), $param1, $param2);
	}
	else {
		if ($app->get('lang_debug')) {
			if (!\helpers\l10n::check_original_lang($key))
				return $app->get($app->get('PREFIX') . $key) ? "<span style='color:darkorange'>" . $app->get($app->get('PREFIX') . $key) . "</span>" : "<span style='color:red'>{$prefix}{$dict}</span>";
		}
		if ($app->exists($key))
			if ($app->get('lang_debug'))
				return "<span style='color:green'>" . $app->get($key) . "</span>";
			else
				return $app->get($key);
		else {
			$arr = explode('.', $key);
			if ($app->get('lang_debug'))
				return "<span style='color:red'>{$prefix}{$dict}</span>";
			else
				return ucfirst(array_pop($arr));
		}
	}
}

function ___($dict, $param2 = null, $param3 = null) {
	return  __($dict, __('main.' . \helpers\l10n::single_en(Base::instance()->get('class_name'))), $param2, $param3);
}

if (! function_exists('str_ends_with')) {
	function str_ends_with(string $haystack, string $needle): bool {
		$needle_len = strlen($needle);
		return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, - $needle_len));
	}
}
if (!function_exists('str_starts_with')) {
	function str_starts_with($haystack, $needle) {
		return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}

if (!function_exists('array_key_first')) {
	function array_key_first(array $arr) {
		foreach($arr as $key => $unused)
			return $key;
		return NULL;
	}
}