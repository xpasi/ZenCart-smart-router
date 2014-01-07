<?php

/*
	Session language info needs to be set before the router and router needs to be set before template stuff,
	so we need to move the language init a bit down in the list.
*/
$autoLoadConfig[98][] = array(
	'autoType'=>'init_script',
	'loadFile'=> 'init_languages.php'
);

$autoLoadConfig[99][] = array(
	'autoType'=>'init_script',
	'loadFile'=>'prr_router.php'
);

// Remove the default language init file inclusion from autoload array
foreach ($autoLoadConfig[110] as $key => $val) {
	if (isset($val['loadFile']) && $val['loadFile'] == 'init_languages.php') {
		unset($autoLoadConfig[110][$key]);
	}
}