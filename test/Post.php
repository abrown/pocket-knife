<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Post extends AppObjectJSON{
    protected $__path = 'test-db';
    public $id;
    public $title;
    public $content;
    public $author;
    public $created;
    public $modified;
}