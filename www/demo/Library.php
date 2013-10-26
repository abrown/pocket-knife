<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Library extends ResourceList {

    public $name = 'Savannah Public Library';
    public $location = '2002 Bull Street, Savannah, GA 31401';
    protected $item_type = 'Book';
    protected $storage = array('type' => 'json', 'location' => '../data/books.json');

    public function OUTPUT_TRIGGER(Representation $representation) {
        // filter HTML responses on HTTP method
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
                    Cache::clearAll();
                    WebHttp::redirect(WebUrl::createAnchoredUrl('library', false));
                    break;
            }
        }
        return $representation;
    }

}