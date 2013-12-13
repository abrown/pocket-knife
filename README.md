pocket-knife
============

_pocket-knife_ is a RESTful, PHP web service framework. It takes care of the 
plumbing while you focus on building RESTful resources (the data schema and 
actions) and representations (the data format); of course _pocket-knife_ already
includes common representation types like JSON, XML, HTML as well as a handful
of storage methods like PDO, CouchDB, and Mongo to link your resources to a 
database. 

Example
-------

Because it is RESTful, it is simple enough to understand at a glance: 

_pocket-knife_ links this ```http://example.com/api.php/thing/1``` (allowing 
GET, PUT, POST, DELETE actions) to this:

```PHP
<?php
// build a Resource; this uses the Item pattern, which assumes it is in a list
class Thing extends ResourceItem {
    public $property_a;
    public $property_b;
    protected $storage = array('type' => 'pdo', 'location' => 'localhost', 
        'database' => 'example', 'username' => 'test', 'password' => 'test',
        'table' => 'things', 'primary' => 'id'); // 'primary' == primary key
}
```

The key to making this happen is a file like this one, _api.php_:

```PHP
<?php
require 'path/to/pocket-knife/files/.../start.php';
require 'path/to/resource/.../Thing.php';

// build settings, not much needed here; can be loaded from a JSON or PHP file
$settings = new Settings();
$settings->set('acl', true); // allow all types of requests for this example
$settings->set('representations', array('text/html', 'application/json', 
    'application/xml')); // allow only these representation types

// start web service
$service = new Service($settings);
$service->execute();
```

Demo
----

For a working demo:
 1. Download the ZIP
 2. Extract it in your Apache public directory
 3. Visit ```http://yoursite.com/pocket-knife/www```

Documentation
-------------

A lot more goes on behind the scenes, but that was the simple start-up guide; 
for more detail see the in-progress [documentation]
(https://github.com/andrewsbrown/pocket-knife/wiki). There you can learn about
features like:
 + Resource patterns
 + Authentication and finely-granular ACLs
 + OAuth Authentication
 + Caching
 + HTTP Content-Type detection
 + Storage methods
 + Testing
 + Auto-updates

Issues
------

Please post all issues to the [issues]
(https://github.com/andrewsbrown/pocket-knife/issues) page. The project is small
enough at this stage that I can respond and fix anything you may find rather 
quickly.