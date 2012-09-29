<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

// setup
include('../start.php');

// build settings
$settings = new Settings(array(
	'location' => 'docs',
	'acl' => true,
	'storage' => array('type'=>'json', 'location' => 'docs/api-map.json'),
	'template' => 'ui/site-template.php',
	'admin' => 'ui/admin-template.php'
));

// run site
$site = new Site( $settings );
$site->execute();