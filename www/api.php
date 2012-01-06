<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

// setup
include('../start.php');
include('classes/SiteApi.php');

// build settings
$settings = new Settings(array(
	'location' => '..',
	'acl' => true,
	'storage' => array('type'=>'json', 'location' => 'api-map.json'),
	'template' => 'ui/api-template.php'
));

// run site
$site = new SiteApi( $settings );
$site->execute();