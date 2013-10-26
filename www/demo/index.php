<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
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

// 
$configuration = new Settings();
$configuration->load('config.json');
$service = new Service($configuration);
$service->execute();