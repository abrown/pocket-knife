<?php
require '../../start.php';



// build settings, not much needed here; can be loaded from a JSON or PHP file
$settings = new Settings();
$settings->set('acl', true); // allow all types of requests for this example
$settings->set('representations', array('text/html', 'application/json', 
    'application/xml')); // allow only these representation types

// start web service
$service = new Service($settings);
$service->execute();