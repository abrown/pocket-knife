<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Book extends ResourceItem{
    
    public $title;
    public $author;
    public $published;
    public $number_of_copies;
    
    protected $storage = array('type' => 'json', 'location' => 'books.json');
    protected $template = array('book-template.php', WebTemplate::PHP_FILE);
    
}