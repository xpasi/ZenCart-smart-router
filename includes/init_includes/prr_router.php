<?php

class p {
	private static $e = array();
	public static function err($set=null) {
		if ($set != null) {
			self::$e[] = $set;
		}
		if (count(self::$e)) return self::$e;
		return '';
	}
}

if (!defined('PRR_ROUTING_TABLE')) {
 define('PRR_ROUTING_TABLE', DB_PREFIX . 'prr_routing_table');
}





class prr_router {

	private static $data = array('parameters' => array());
	private static $callbacks = array();

	// TODO: Make it cache
	public static function route() {
		global $db;
		// Set page to the request, so new pages can be accessed without a database entry
		$_GET['main_page'] = self::request();

		// Start searching the most exact match from the route table
		$path = explode('/',self::request());
		$match = false;
		$sql = 'SELECT `callback` FROM `' . PRR_ROUTING_TABLE . '` WHERE `route` = :partial AND `lang` = :language';
		$sql = $db->bindVars($sql, ':language', $_SESSION['languages_code'], 'string');
		while (count($path)) {
			self::$data['route'] = implode('/',$path);
			// Search for the partial
			$exec = $db->bindVars($sql, ':partial', self::$data['route'], 'string');
			$found = $db->Execute($exec);
			if (isset($found->fields['callback']) && $found->fields['callback'] != '') {
				$match = true;
				self::$data['callback'] = $found->fields['callback'];
				// Set main_page to the callback name (it is the directory name under pages)
				$_GET['main_page'] = self::$data['callback'];
			}
			if ($match) break;
			array_unshift(self::$data['parameters'], array_pop($path));
		}

		// Include the routing file if any
		$pagedir = DIR_FS_CATALOG . DIR_WS_MODULES . '/pages/' . self::$data['callback'];
		$routefile = '/route.php';
		if (is_dir($pagedir)) {
			// Route for page found, set the main_page now and override it later if needed!
			if (file_exists($pagedir . $routefile)) {
				include($pagedir . $routefile);
				$class = 'route_' . self::$data['callback'];
			}
			// Attach a callback class if it exists
			if (class_exists($class)) {
				self::attach($class);
				// Call route() on the callback class and pass parameters to it, which should set all the $_GET parameters to view the page
				self::$callbacks[self::callback()]->route(self::$data['route'], self::$data['parameters']);
			}
		}
	}

	// Get current request
	public static function request() {
		if (!isset(self::$data['request'])) {
			self::$data['request'] = trim($_GET['q'],' /');
			// Unset 'q', it will not be used beyond this point
			unset($_GET['q']);
		}
		return self::$data['request'];
	}

	// Attaches a routing callback class to the router
	private function attach($name,$callback=null) {
		if ($callback === null) $callback = self::$data['callback'];
		if (!isset(self::$callbacks[$callback])) self::$callbacks[$callback] = new $name;
	}

	// Returns the callback name
	public function callback($set=false) {
		if ($set !== false) {
			self::$data['callback'] = $set;
		}
		return self::$data['callback'];
	}

	public function link($url) {
		global $db;
		$original = parse_url($url);
		$route = null;
		$callback = null;
		foreach (explode('&',htmlspecialchars_decode($original['query'])) as $q) {
			$qq = explode('=',$q);
			$query[$qq[0]] = $qq[1];
		}

		// No main_page defined, there is no way of knowing what to do. Pass the link as is. 
		if (!isset($query['main_page'])) return $url;
 
		// Determine which "module" should hande the request from database
		$sql = 'SELECT `route`,`callback`,`lang` FROM `' . PRR_ROUTING_TABLE . '` WHERE `callback` = :partial';
		$sql = $db->bindVars($sql, ':partial', $query['main_page'], 'string');
		$found = $db->Execute($sql);
		$ownlang = false;

		while (!$found->EOF) {
			$route = $found->fields['route'];
			$callback = $found->fields['callback'];
			if ($_SESSION['languages_code'] == $found->fields['lang']) {
				// Language specific found
				$ownlang = true;
				break;
			}
			$found->MoveNext();
		}

		if ($ownlang == false && $callback !== null) {
			// Found a route, but in a different language... Should use the callback name as route
			$route = $found->fields['callback'];
			$callback = $found->fields['callback'];
		}

		// If non found, check if there is a "page" directory for the entry
		if ($callback === null) {
			$pagedir = DIR_FS_CATALOG . DIR_WS_MODULES . '/pages/' . $query['main_page'];
			if (is_dir($pagedir)) {
				$route = $query['main_page'];
				$callback = $query['main_page'];
			}
		}

		// unset main_page here as it's not needed anymore and this way it doesn't need to be unset in every router sub class.
		unset($query['main_page']);

		// If no destination found, return the original link, as the destination is unknown.
		if ($callback === null) {
			return $url;
		} else {
			// Destination found.
			$pagedir = DIR_FS_CATALOG . DIR_WS_MODULES . '/pages/' . $callback;
			$routefile = '/route.php';
			if (is_dir($pagedir)) {
				// Route for page found, set the main_page now and override it later if needed!
				if (file_exists($pagedir . $routefile)) {
					$class = 'route_' . $callback;
					if (!class_exists($class)) {
						include($pagedir . $routefile);
					}
				}

				// Attach a callback class if it exists
				if (class_exists($class)) {
					self::attach($class,$callback);
					// Pass control to "module" specific router for fine tuning.
					$tuned = self::$callbacks[$callback]->url($query);
					/*
						a sub class url() method should return an array in the following format:
					
						array(
							path => array(
								'paths',
								'of',
								'the',
								'url',
							)
							query => array(
								'any' => 'remaining',
								'query' => 'string',
								'parts' => 'of the original array',
							)
						)
					
					*/
				}
			}
		}


		// Rebuild the complete url (Yes, I know, it doesn't support "user:pass@" credentials... Feeling lazy!
		$url = $original['scheme'] . '://';
		$url .= $original['host'];
		$url .= (isset($original['port']) && $original['port'] != '') ? ':' . $original['port'] : '';
		$url .= '/' . $route . '/';

		$set = false;
		if (isset($tuned['path']) && count($tuned['path'])) {
			$url .= implode('/',$tuned['path']);
			$set = true;
		}
		if (isset($tuned['query']) && count($tuned['query'])) {
			$url .= '?';
			foreach ($tuned['query'] as $k => $v) $d[] = $k . '=' . $v;
			$url .= implode('&',$d);
			$set = true;
		}

		// Handle a case where there was a query string to begin with (exluding main_page), but sub-router did not return anything.
		if (count($query) && !$set) {
			// Copy the original parameters to the new url
			$url .= '?';
			foreach ($query as $k => $v) $d[] = $k . '=' . $v;
			$url .= implode('&',$d);
		}

		$url .= (isset($original['fragment']) && $original['fragment'] != '') ? '#' . $original['fragment'] : '';

		return $url;
	}

}

// Initialize routing ONLY if main_page is not already set!
if (!isset($_GET['main_page'])) {
	prr_router::route();
}

p::err($_GET['main_page']);