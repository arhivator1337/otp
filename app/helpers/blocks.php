<?php
namespace helpers;

class blocks {

	private static function block_generator($params = []) {
		return \View::instance()->render($params['tpl'], 'text/html', $params);
	}

	public static function get($block, $value = [], $params = []) {
		$vars = [
			'data' => $value,
			'tpl' => "blocks\\" . $block . ".html",

		] + $params;
		return self::block_generator($vars);
	}
}