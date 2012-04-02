<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Library extends ResourceList{
    public $name;
    public $location;
    public $books;
    
    protected $item_type = 'Book';
    
    protected $storage = array('type' => 'json', 'location' => 'books.json');
    protected $template = array('book-template.php', WebTemplate::PHP_FILE);
}