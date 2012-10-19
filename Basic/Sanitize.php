<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Sanitize input for safe use.
 * @uses
 */
class BasicSanitize{
    
    /**
     * Remove all but letters, numbers, and limited punctuation (space, hyphen,
     * underscore, period, and comma).
     * @param string $string
     * @return string
     */
    public static function toText($string){
        return preg_replace('#[^a-zA-Z0-9 \-_\.,\[\]]#', '', $string);
    }
}