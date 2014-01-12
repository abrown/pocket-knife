<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class ClientTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {

    }

    public function testBuildingClients(){
        $client = new Client();
        $this->assertEquals('anonymous', $client->username);
        $this->assertEquals('localhost', $client->ip); // phpunit is probably testing from the local machine
        $this->assertEquals('cli', $client->browser); // ... and from the command line
    }
}