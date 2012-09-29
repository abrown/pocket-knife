<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class SiteTest extends PHPUnit_Extensions_OutputTestCase {

    public static function setUpBeforeClass() {
        // start pocket knife
        $path = dirname(dirname(__FILE__));
        require_once $path . '/start.php';
        // get code
        autoload('BasicClass');
        BasicClass::autoloadAll('Site');
        BasicClass::autoloadAll('StorageJson');
    }

    public function setUp() {
        $settings = new Settings(array(
            'location' => './data',
            'acl' => true,
            'storage' => array('type'=>'json', 'location'=>'data/site-map.json')
        ));
        $this->site = new Site( $settings );
    }
    
    public function testFind(){
        $expected = dirname(__FILE__).DS.'data'.DS.'example.php';
        $actual = $this->site->find('example.php');
        $this->assertEquals($expected, $actual);
    }

    public function testSiteMap() {
        // get expected filesfiles
        chdir('data');
        $expected = glob('*');
        chdir('..');
        // get site map
        $actual = $this->site->getSiteMap();
        // test
        foreach($expected as $file){
            $this->assertContains($file, (array) $actual);
        }
    }
    
    public function testExecute() {
        
    }
}