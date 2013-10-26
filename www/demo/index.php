<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 * In 148 lines of PHP and 60 of CSS, this demo builds a mock library system
 * accessible by both browsers and JSON API clients
 */

require '../../start.php';
require 'Book.php';
require 'Library.php';

// redirect if no tokens
try{
    WebUrl::getTokens();
}
catch(Error $e){
    WebHttp::redirect(WebUrl::createAnchoredUrl('library'));
}

// start service
$configuration = new Settings();
$configuration->load('config.json');
$service = new Service($configuration);
$service->execute();