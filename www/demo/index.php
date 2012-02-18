<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

require '../../start.php';
$configuration = new Settings();
$configuration->load('config.json');
$service = new Service();
$service->execute();