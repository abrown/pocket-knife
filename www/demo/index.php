<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 * In 148 lines of PHP and 60 of CSS, this demo builds a mock library system
 * accessible by both browsers and JSON API clients
 */

require '../../start.php'; // load pocket-knife files
require 'Book.php'; // load Book class
require 'Library.php'; // load Library class

// if no tokens are added to the URL, the user probably wants to view the library
try{ WebUrl::getTokens(); }
catch(Error $e){ WebHttp::redirect(WebUrl::createAnchoredUrl('library')); }

// start service
$configuration = new Settings();
$configuration->load('config.json'); // load settings from a JSON file
$service = new Service($configuration);
$service->execute();