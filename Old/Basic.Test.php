<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class Test{
    
    public static $class;
    public static $feature;
    public static $instance;

    /**
     * Constructor
     */
    public function __construct($class = null, $instantiate = true){
        if( $class ){
            self::$class = $class;
            echo "<h3>Testing '$class'</h3>";
            if( $instantiate ){
                self::$instance = new $class;
                self::test( $class.' successfully constructed', '$instance');
            }
        }
        // setup
        assert_options(ASSERT_ACTIVE,     1);
        assert_options(ASSERT_WARNING,    0);
        assert_options(ASSERT_BAIL,       0);
        assert_options(ASSERT_QUIET_EVAL, 0);
        assert_options(ASSERT_CALLBACK, array('Test', 'error'));
    }

    /**
     * Test a feature
     * @param <type> $feature
     * @param <type> $code
     */
    public function test($feature, $code){
        self::$feature = $feature;
        $instance = self::$instance;
        if( assert($code) ) self::ok($code);
    }

    /**
     * Success handler
     * @param <string> $feature
     * @param <string> $code
     */
    public static function ok($code){
        $feature = self::$feature;
        echo "<p class='success'>$feature<br/><span class='mute'>$code</span></p>";
    }

    /**
     * Error handler
     * @param <string> $file
     * @param <string> $line
     * @param <string> $code
     */
    public static function error($file, $line, $code){
        $feature = self::$feature;
        echo "<p class='error'>$feature<br/><span class='mute'>$code ($file:$line)</span></p>";
    }
}