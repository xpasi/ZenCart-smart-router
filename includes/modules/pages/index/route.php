<?php

class route_index {

	private $names = array();

	// Sub route
	public function route($page,$params) {
		global $db;
		// Assume that all params to the index page are category names and that the last name is what we want to show
		$sql = 'SELECT `categories_id` as `id`,REPLACE(`categories_name`," ","-") as `name` FROM `' . TABLE_CATEGORIES_DESCRIPTION . '` WHERE `language_id` = :language';
		$sql = $db->bindVars($sql, ':language', $_SESSION['languages_id'], 'string');
		$sub_sql = '';
		foreach ($params as $key => $par) {
			$params[$key] = strtolower($par);
			$sub_sql .= $db->bindVars(' OR REPLACE(`categories_name`," ","-") = :name', ':name', $par, 'string');
		}
		$res = $db->Execute($sql . $sub_sql);
		while (!$res->EOF) {
			$name = strtolower($res->fields['name']);
			$tree[$res->fields['id']] = $name;
			// Build an cPath compatible string
			$key = array_search($name,$params);
			if ($key !== false) {
				$cPath[$key] = $res->fields['id'];
			}
			$res->MoveNext();
		}
		ksort($cPath);
		$cPath = implode('_',$cPath);

		$_GET['cPath'] = $cPath;
	}

	public function url($url) {
		global $db;
		// Rewrite an url to point into an alias
		$url_str = '';
		if (isset($url['cPath'])) {
			// cPath is set, get the group names
			foreach (explode('_',$url['cPath']) as $id) {
				$ids[(int) $id] = '';
				// Don't get name it is already cached!
				if ($this->names[$id]) continue;
				$sql_ids[] = (int) $id;
			}
			// If some id's don't have names yet, get them
			if (count($sql_ids)) {
				$sql_ids = implode(',',$sql_ids);
				$sql = 'SELECT `categories_id`,REPLACE(`categories_name`," ","-") as `name` FROM 	`' . TABLE_CATEGORIES_DESCRIPTION . '` WHERE `categories_id` IN (' . $sql_ids . ') AND `language_id` = :lang';
				$sql = $db->bindVars($sql, ':lang', $_SESSION['languages_id'], 'string');
				$names = $db->execute($sql);

				while (!$names->EOF) {
					// TODO: Make the name string compatible with this system!
					$this->names[$names->fields['categories_id']] = $names->fields['name'];
					$names->MoveNext();
				}
			}
			// Build a string
			foreach ($ids as $key => $id) {
				$return['path'][] = $this->names[$key];
			}
		}
		// cPath no longer needed
		unset($url['cPath']);
		$return['query'] = $url;
		return $return;
	}



}
