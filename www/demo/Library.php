<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Library extends ResourceList {
    // publicly accessible properties will be viewable/editable by clients
    public $name = 'Savannah Public Library';
    public $location = '2002 Bull Street, Savannah, GA 31401';
    // we declare the ResourceItem type here, see book.php
    protected $item_type = 'Book';
    // this property defines where the data will be stored, see Storage folder for more
    protected $storage = array('type' => 'json', 'location' => '../data/books.json');
    // because Library is such a bland type, we do not override the GET/PUT/
    // POST/DELETE methods defined in ResourceList; we do, however, use OUTPUT_TRIGGER,
    // fired before the Representation is sent back to the client, to add some
    // templating to our HTML responses.
    public function OUTPUT_TRIGGER(Representation $representation) {
        // delete the entire cache for non-idempotent methods
        if( WebHttp::getMethod() == 'PUT' || WebHttp::getMethod() == 'POST' || WebHTTP::getMethod() == 'DELETE'){
            Cache::clearAll();
        }
        // add HTML templates and redirects
        if ($representation->getContentType() == 'text/html') {
            switch (WebHttp::getMethod()) {
                case 'GET':
                    $representation->setTemplate('template-library.php', WebTemplate::PHP_FILE);
                    break;
                case 'OPTIONS':
                    $representation->setTemplate('template-options.php', WebTemplate::PHP_FILE);
                    break;
                case 'PUT':
                case 'POST':
                case 'DELETE':
                    // non-idempotent requests will redirect to the main page
                    WebHttp::redirect(WebUrl::createAnchoredUrl('library', false));
                    break;
            }
        }
        return $representation;
    }
}