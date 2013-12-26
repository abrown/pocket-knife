<?php

/**
 * @copyright Copyright 2013 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
define('LOG_LOCATION', get_base_dir() . DS . 'writeable' . DS . 'logs');

/**
 * Provides logging capabilities
 * @example
 * BasicLog::error("The file '...' could not be found.", 404);
 * BasicLog::request($representation); // logs current HTTP request
 * BasicLog::response($representation); // logs current HTTP response
 * @uses Error
 */
class BasicLog {

    /**
     * Log locations
     * @var array
     */
    static protected $locations = array();

    /**
     * Append to error log file
     * @param string $message
     * @param int $code
     * @param mixed $trace
     * @return true;
     */
    static public function error($message, $code = 0, $trace = null) {
        $_message = '';
        if ($code) {
            $_message .= "$code: ";
        }
        $_message .= $message;
        if ($trace !== null) {
            if (is_array($trace)) {
                $_message .= implode("\n", $trace);
            } else {
                $_message .= "\n" . $trace;
            }
        }
        return self::append($_message, self::getFile('error'));
    }

    /**
     * Log an HTTP response
     * @param Representation $request
     * @param boolean $include_data
     * @return boolean
     */
    static public function request(Representation $request, $include_data = false) {
        $_message = 'Request: ' . WebUrl::getUrl();
        $_message .= ' (' . $request->getContentType() . ')';
        if ($include_data) {
            $_message .= "\n" . $request->__toString();
        }
        return self::append($_message, self::getFile('http'));
    }

    /**
     * Log an HTTP response
     * @param Representation $response
     * @param boolean $include_data
     * @return boolean
     */
    static public function response(Representation $response, $include_data = false) {
        $_message = 'Response: ' . WebUrl::getUrl();
        $_message .= ' (' . $response->getContentType() . ')';
        if ($include_data) {
            $_message .= "\n" . $response->__toString();
        }
        return self::append($_message, self::getFile('http'));
    }

    /**
     * Append a log message to the given file; should be atomic per 
     * http://stackoverflow.com/questions/5479580/
     * @param string $message
     * @param string $file
     */
    static public function append($message, $file) {
        if (self::checkOrCreateFile($file)) {
            $line = sprintf("[%s] %s\n", date('c'), $message);
            file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
            return true;
        }
        return false;
    }

    /**
     * Return the log file; defaults to 'writeable/logs/...'
     * @param $name [error|request|response|...]
     * @return string 
     */
    static public function getFile($name = 'error') {
        if (!array_key_exists($name, self::$locations)) {
            self::$locations[$name] = LOG_LOCATION . DS . $name . '.log';
        }
        return self::$locations[$name];
    }

    /**
     * Set the log file location for a given name
     * @param string $file
     * @param string $name [error|request|response|...]
     * @throws Error
     */
    static public function setFile($file, $name = 'error') {
        self::$locations[$name] = $file;
    }

    /**
     * Verify that a file exists and can be written to; if it can't, this
     * method attempts to create it and its directories.
     * @param string $file
     * @return boolean
     * @throws Error
     */
    static private function checkOrCreateFile($file) {
        if (is_file($file) && is_writeable($file)) {
            return true;
        }
        // make directories
        if (!file_exists(dirname($file))) {
            $success = mkdir(dirname($file), 0777, true);
            if (!$success) {
                throw new Error("Unable to create necessary directories for log '{$file}' to exist.", 500);
            }
        }
        // make file
        if (!file_exists($file)) {
            $bytes = file_put_contents($file, '');
            if ($bytes === false) {
                throw new Error("Unable to create log '{$file}'.", 500);
            }
        }
        // check writeable
        if (!is_writeable($file)) {
            throw new Error("Could not write to log '{$file}'; ensure the file is writeable.", 500);
        }
        // return
        return true;
    }

}