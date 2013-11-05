<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Book extends ResourceItem {

    public $title;
    public $author;
    public $published;
    public $number_of_copies;
    protected $storage = array('type' => 'json', 'location' => '../data/books.json');
  
    
    /**
     * Return a Book even if no ID is supplied; this allows GET requests
     * with no ID to not return errors in a situation where an HTML client
     * wants to create a Book with 'api.php/book'
     * @return \Book
     */
    public function GET(){
        if( !$this->getID() ){
            return $this;
        }
        else{
            return parent::GET();
        }
    }
    
    /**
     * Modify input representation
     * @param Representation $representation
     * @return Representation 
     */
    public function INPUT_TRIGGER(Representation $representation) {
        // do nothing
        return $representation;
    }

    /**
     * Modify output representation
     * @param Representation $representation
     * @return Representation 
     */
    public function OUTPUT_TRIGGER(Representation $representation) {
        // switch from form data to HTML
        if ($representation->getContentType() == 'application/x-www-form-urlencoded') {
            $representation->setContentType('text/html');
        }
        // filter HTML responses on HTTP method
        if ($representation->getContentType() == 'text/html') {
            switch (WebHttp::getMethod()) {
                case 'GET':
                    if (WebHttp::getParameter('edit')) {
                        $representation->setTemplate('template-edit-book.php', WebTemplate::PHP_FILE);
                    } else {
                        $representation->setTemplate('template-view-book.php', WebTemplate::PHP_FILE);
                    }
                    break;
                case 'OPTIONS':
                    $representation->setTemplate('template-options.php', WebTemplate::PHP_FILE);
                    break;
                case 'PUT':
                case 'POST':
                case 'DELETE':
                    WebHttp::redirect(WebUrl::createAnchoredUrl('library', false));
                    break;
            }
        }
        return $representation;
    }

}