<?php

class route_product_info {

	// Sub route
	public function route($page,$params) {
		global $db;
		if (isset($params[0]) && $params[0] != '') {
			// Assuming param 0 to be the product name
			$sql = 'SELECT `products_id` FROM `' . TABLE_PRODUCTS_DESCRIPTION . '` WHERE REPLACE(`products_name`," ","-") = :name AND `language_id` = :language';
			$sql = $db->bindVars($sql, ':language', $_SESSION['languages_id'], 'string');
			$sql = $db->bindVars($sql, ':name', $params[0], 'string');
			$res = $db->Execute($sql);
			if (isset($res->fields['products_id']) && $res->fields['products_id'] != '') {
				$_GET['products_id'] = $res->fields['products_id'];
			}
		}
	}

	public function url($url) {
		// Rewrite an url to point into an alias

	}

}
