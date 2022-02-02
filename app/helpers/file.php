<?php
namespace helpers;

class file
{
	public static function check_upload($form_field_name = 'file', $type = 'text/csv') {
		if ($_FILES[$form_field_name]["size"] == 1)
			return false;

		if(is_array($type)) {
			if (!in_array($_FILES[$form_field_name]['type'], $type))
				return false;
		}
		else {
			if ($_FILES[$form_field_name]['type'] != $type)
				return false;
		}
		return $_FILES[$form_field_name]['tmp_name'];
	}

	public static function parse_csv($filepath, $delimiter = ',') {
		return array_map(
			function($v) use ($delimiter) {return str_getcsv($v, $delimiter);}, file($filepath)
		);
	}
}