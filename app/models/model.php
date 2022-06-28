<?
namespace models;

class Model extends \Prefab{
	var $app, $db, $cache;

	function __construct() {
		$this->app = \Base::instance();
		$this->db = \Base::instance()->get('db');
		$this->cache = \Cache::instance();
	}

	function query_gen($sql, $params = [], $debug = false) {
		$pdo_params = $_params = [];
		foreach ($params as $pdo => $value) {
			if($value === null or $value === false or (is_array($value) && empty($value)))
				continue;

			if($pdo == 'limit' or $pdo == ':limit') {
				$pdo_params[':limit'] = $value;
				continue;
			}

			if($pdo == 'group_by')
				continue;

			preg_match('/:[a-zA-Z_]+/m', $pdo, $matches);

			if(is_array($value)) {
				$in_params = [];
				foreach ($value as $id => $key) {
					if($key !== false) {
						$pdo_params[$matches[0] . $id] = $key;
						$in_params[] = $matches[0] . $id;
					}
				}
				$_params[] = str_replace($matches[0], implode(',', $in_params), $pdo);
			}
			elseif(!empty($matches[0])) {
				$_params[] = $pdo;
				$pdo_params[$matches[0]] = $value;
			}

		}

		if(!empty($_params)) {
			$str_params = " WHERE " . implode(' and ', $_params);
			$sql = str_replace('%where%', $str_params, $sql);
		}

		if(!empty($params['offset'])) {
			$sql = str_replace('%offset%', 'OFFSET ' . $params['offset'], $sql);
		}

		if(!empty($params['group_by']))
			$group_params = 'GROUP BY ' . $params['group_by'];
			$sql = str_replace('%group_by%', $group_params, $sql);

		$sql = str_replace(['%where%', '%group_by%', '%offset%'], '', $sql);

		if($debug == 1) {
			echo '<pre>';
			print_r($params);
			echo '</pre>';
			echo '<pre>';
			print_r(['str_params' => $str_params, 'group_by' => $group_params, 'pdo_params' => $pdo_params, 'sql' => $sql . ';']);
			die('<pre>');
		}

		return $this->db->exec($sql, $pdo_params);
	}

//	function generate_rand_pdo_name() { }


	function to_array($db_res, &$model, $number2id = false) {
		foreach ($db_res as $o)
			$t[] = $model->cast($o);
		if($number2id && !empty($t))
			$t = $this->i2id($t);
		return is_array($t) ? $t :  [];
	}

	function i2id(&$array) {
		for ($i = 0; $i < count($array); $i++)
			$_arr[$array[$i]['id']] = $array[$i];
		return $_arr;
	}

	function array2sql_params($array, $type = 'string')
	{
		$return_array = [];
		$glue = ',';

		foreach ($array as $key => $val) {
			if (is_int($key)) {
				$return_array[] = $val;
				$glue = ' OR ';
			} else {
				$return_array[] = "{$key} = '{$val}'";
			}
		}

		if ($type == 'array')
			return $return_array;

		return implode($glue, $return_array);
	}

	function escape_title($arr) {
		return $this->escape($arr, '`');
	}

	function escape($arr, $esc = "'") {
		if (is_array($arr))
			array_walk($arr, function (&$v) use ($esc) {
				$v = $esc . $v . $esc;
			});
		else
			$arr = $esc . $arr . $esc;

		return $arr;
	}

	function insert_batch(string $table, array $keys, array $data) {
		foreach ($data as $val) {
			if (is_array($val))
				$str = implode(',', $this->escape($val));
			else
				$str = $this->escape($val);

			$node[] = " ({$str}) ";
		}

		$_keys = implode(',', $this->escape_title($keys));

		$sql = ("INSERT INTO `{$table}` ({$_keys}) VALUE "
			. implode(',', $node)
			. " ON DUPLICATE KEY UPDATE id=id"
		);
		$this->log['sql'] = $sql;
		$this->log['insert_count'] = count($node);

		return $this->db->exec($sql);
	}

	function insert_batch_pdo(string $table, array $keys, array $data, $update_key = 'id') {
		$params = $nodes = $values = [];

		foreach ($data as $id => $val) {
			foreach ($val as $i => $v) {
				$params[':v' . $id . '_' . $i] = $v;
				$values[] = ':v' . $id . '_' . $i;
			}
			$nodes[] = '(' . implode(',', $values) . ')';
			$values = [];

		}
		$_keys = implode(',', $this->escape_title($keys));

		$sql = ("INSERT INTO `{$table}` ({$_keys}) VALUE "
			. implode(',', $nodes)
			. " ON DUPLICATE KEY UPDATE {$update_key}={$update_key}"
		);
		$this->log['sql'] = $sql;
		$this->log['insert_count'] = count($values);
		return $this->db->exec($sql, $params);
	}

	public function map_id($array, $id_to_map = 'id') {
		$res = [];
		foreach ($array as $i => $ar)
			$res[$ar[$id_to_map]] = $ar;
		return $res;
	}

	public function map_key_val($array, $id_to_key = 'id', $id_to_val) {
		$res = [];
		foreach ($array as $i => $ar)
			$res[$ar[$id_to_key]] = $ar[$id_to_val];
		return $res;
	}

	public function map_id_nested($array, $id_to_map, $nested_id_to_map = false) {
		$res = [];
		for ($i = 0; $i < count($array); $i++) {
			if($nested_id_to_map) {
				if (!empty($array[$i][$id_to_map]) && $array[$i][$nested_id_to_map])
					$res[$array[$i][$id_to_map]][$array[$i][$nested_id_to_map]] = $array[$i];
			} else {
				if (!empty($array[$i][$id_to_map]))
					$res[$array[$i][$id_to_map]][] = $array[$i];
			}
		}
		return $res;
	}

}