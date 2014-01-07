<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Stores each record as a file in a writable directory
 * @uses StorageInterface, BasicValidation, Error
 */
class StorageFile implements StorageInterface {

    /**
     * Stores the path to the file directory
     * @var string 
     */
    public $location;

    /**
     * Defines the format in which to store the data; one of
     * [json, php, config, raw]
     * @var string
     */
    public $format = 'json';

    /**
     * Current list of records to read/store
     * @var array 
     */
    protected $data = array();

    /**
     * Whether the request changes the data
     * @var boolean 
     */
    protected $isChanged = false;

    /**
     * Whether a file must be written
     * @var boolean
     */
    protected $mustWrite = false;

    /**
     * Constructor
     * @param Settings $settings 
     */
    public function __construct($settings) {
        // determines what settings must be passed
        BasicValidation::with($settings)
                ->isSettings()
                ->withProperty('location')
                ->isString()
                ->upOne()
                ->withProperty('format')
                ->oneOf('json', 'php', 'config', 'raw');
        // import settings
        $settings->copyTo($this);
        // ensure directory exists
        if (!is_dir($this->location)) {
            throw new Error("StorageFile requires a directory in which to store files; the current directory does not exist: '{$this->location}'.", 500);
        }
        // ensure directory is writable
        if (!is_writable($this->location)) {
            throw new Error("StorageFile cannot store files in '{$this->location}' because the directory is not writable.", 500);
        }
        // set location to actual path
        $this->location = realpath($this->location);
    }

    /**
     * Begins transaction
     */
    public function begin() {
        // do nothing
    }

    /**
     * Complete transaction; write files if necessary
     */
    public function commit() {
        if ($this->mustWrite) {
            foreach ($this->data as $id => $record) {
                $path = $this->getPath($id);
                // create file if necessary
                if (!file_exists($path)) {
                    $this->createPath($path);
                }
                // check that no ID is attempting to modify files above the location directory
                if (!$path || strlen($path) < strlen($this->location)) {
                    throw new Error("The ID '{$id}' produced an invalid path of '{$path}'; StorageFile cannot write to this path.", 500);
                }
                // write
                if ($this->format == 'json') {
                    self::writeJson($path, $record);
                } elseif ($this->format == 'php') {
                    self::writePhp($path, $record);
                } elseif ($this->format == 'config') {
                    self::writeConfig($path, $record);
                } elseif ($this->format == 'raw') {
                    self::writeRaw($path, $record);
                }
            }
        }
        // clear data
        $this->data = array();
        $this->mustWrite = false;
        $this->changed = false;
        // release locks
    }

    /**
     * Rolls back transaction
     */
    public function rollback() {
        $this->data = array();
        $this->mustWrite = false;
        $this->changed = false;
    }

    /**
     * Returns true if data has been modified
     * @return boolean 
     */
    public function isChanged() {
        return $this->isChanged;
    }

    /**
     * Create record
     * @param mixed $record
     * @param mixed $id 
     */
    public function create($record, $id = null) {
        // set ID
        if (!$id) {
            $last = $this->getLastID();
            if (!$last)
                $id = 1;
            elseif (is_numeric($last))
                $id = (int) $last + 1;
            else
                $id = $last . '$1';
            // save ID in record if necessary
            if (is_object($record) && property_exists($record, 'id')) {
                $record->id = $id;
            }
        }
        // set data
        $this->data[$id] = $record;
        // set as changed
        $this->mustWrite = true;
        $this->isChanged = true;
        // return
        return $id;
    }

    /**
     * Read record
     * @param mixed $id
     * @return mixed 
     */
    public function read($id) {
        if (is_null($id)) {
            throw new Error('READ action requires an ID', 400);
        }
        if (!$this->exists($id)) {
            throw new Error("READ action could not find ID '$id'", 404);
        }
        // read
        $path = $this->getPath($id);
        if ($this->format == 'json') {
            return self::readJson($path);
        } elseif ($this->format == 'php') {
            return self::readPhp($path);
        } elseif ($this->format == 'config') {
            return self::readConfig($path);
        } elseif ($this->format == 'raw') {
            return self::readRaw($path);
        }
        // return
        return null;
    }

    /**
     * Update record
     * @param mixed $record
     * @param mixed $id 
     */
    public function update($record, $id) {
        if (is_null($id)) {
            throw new Error('UPDATE action requires an ID', 400);
        }
        if (!$this->exists($id)) {
            throw new Error("UPDATE action could not find ID '$id'", 400);
        }
        // read in
        $this->data[$id] = $this->read($id);
        // case: data is not an object
        if (is_scalar($record)) {
            $this->data[$id] = $record;
        } else {
            // case: data used to be scalar
            if (is_scalar($this->data[$id])) {
                $this->data[$id] = new stdClass();
            }
            // change each field
            foreach ($record as $key => $value) {
                $this->data[$id]->$key = $value;
            }
        }
        // set as changed
        $this->mustWrite = true;
        $this->isChanged = true;
        // return
        return $this->data[$id];
    }

    /**
     * Deletes a record
     * @param mixed $id 
     */
    public function delete($id) {
        if (is_null($id)) {
            throw new Error('DELETE action requires an ID', 400);
        }
        if (!$this->exists($id)) {
            throw new Error("DELETE action could not find ID '$id'", 400);
        }
        // get record
        $record = $this->read($id);
        // delete path
        $this->deletePath($this->getPath($id));
        // set as changed
        $this->isChanged = true;
        // return
        return $record;
    }

    /**
     * Tests whether an object with the given ID exists
     * @param mixed $id
     * @return boolean 
     */
    public function exists($id) {
        $path = $this->getPath($id);
        return file_exists($path);
    }

    /**
     * Return all records
     * @return array
     */
    public function all($number_of_records = null, $page = null) {
        $pattern = $this->location . DS . '*.' . $this->format;
        $files = glob($pattern);
        // sort by time, see http://stackoverflow.com/questions/124958
        usort($files, array('StorageFile', 'sort'));
        // do paging
        if (is_int($number_of_records)) {
            if (is_int($page)) {
                $offset = (abs($page) - 1) * $number_of_records;
            } else {
                $offset = 0;
            }
            $files = array_slice($files, $offset, $number_of_records, true);
        }
        // get data
        $data = array();
        foreach ($files as $file) {
            $id = basename($file, '.' . $this->format);
            $data[$id] = $this->read($id);
        }
        // return
        return $data;
    }

    /**
     * Deletes all records
     * @return boolean
     */
    public function deleteAll() {
        // delete all
        $this->deleteAllFromPath($this->location);
        // set as changed
        $this->isChanged = true;
        // return
        return true;
    }

    /**
     * Returns the number of items
     * @return int 
     */
    public function count() {
        $pattern = $this->location . DS . '*.' . $this->format;
        $files = glob($pattern);
        return count($files);
    }

    /**
     * Search for records
     * @param string $key
     * @param mixed $value 
     */
    public function search($key, $value) {
        $data = $this->all();
        // look through each record
        $found = array();
        foreach ($data as $id => $record) {
            if (@$record->$key == $value)
                $found[$id] = $record;
        }
        return $found;
    }

    /**
     * Return the first element; StorageFile organizes by filesystem time modified
     * @return mixed
     */
    public function first() {
        $pattern = $this->location . DS . '*.' . $this->format;
        $files = glob($pattern);
        // sort by time, see http://stackoverflow.com/questions/124958
        usort($files, array('StorageFile', 'sort'));
        // get id
        $id = basename($files[0], '.' . $this->format);
        // return
        return $this->read($id);
    }

    /**
     * Return the last element; StorageFile organizes by filesystem time modified
     * @return mixed
     */
    public function last() {
        // get id
        $id = $this->getLastID();
        // return
        return $this->read($id);
    }

    /**
     * Return the last ID, by modification time
     * @return int
     */
    public function getLastID() {
        // check queue
        if ($this->mustWrite) {
            end($this->data);
            $id = key($this->data);
            return $id;
        }
        // check location
        $pattern = $this->location . DS . '*.' . $this->format;
        $files = glob($pattern);
        // sort by time, see http://stackoverflow.com/questions/124958
        usort($files, array('StorageFile', 'sort'));
        // get id
        $id = basename(end($files), '.' . $this->format);
        // return
        return $id;
    }

    /**
     * Sort the files in a directory by 
     * @param string $a
     * @param string $b
     * @return int
     */
    protected static function sort($a, $b) {
        $time = filemtime($a) - filemtime($b);
        if ($time === 0) {
            return strcmp($a, $b);
        } else {
            return $time;
        }
    }

    /**
     * Return the path for a given ID/file
     * @param string $id
     * @return string
     */
    protected function getPath($id) {
        $path = $this->location . DS . $id . '.' . $this->format;
        return $path;
    }

    /**
     * Create the given path; creates necessary directories.
     * @param string $path
     * @return boolean
     * @throws Error
     */
    protected function createPath($path) {
        if (strlen($path) < strlen($this->location)) {
            throw new Error("Path '{$path}' is outside of directory '{$this->location}'.", 500);
        }
        // get default permissions
        $permissions = fileperms($this->location);
        // create necessary directories
        $dir = dirname($path);
        if ($dir !== $this->location && !is_dir($dir)) {
            mkdir($dir, $permissions, true);
        }
        // create file
        return (boolean) file_put_contents($path, '');
    }

    /**
     * Delete a file, removing any empty directories above it
     * @param string $path
     * @return boolean
     * @throws Error
     */
    protected function deletePath($path) {
        $path = realpath($path);
        if (!file_exists($path)) {
            throw new Error("Path '{$path}' cannot be deleted because it cannot be found.", 404);
        }
        if (strlen($path) < strlen($this->location)) {
            throw new Error("Path '{$path}' is outside of directory '{$this->location}'.", 500);
        }
        // delete file
        unlink($path);
        // delete directories
        $dir = dirname($path);
        while ($dir != $this->location && count(scandir($dir)) == 2 ) {
            rmdir($dir);
            $dir = dirname($dir);
        }
        // return 
        return true;
    }

    /**
     * Clean directory of all files and folders
     * @param string $path
     */
    protected function deleteAllFromPath($path) {
        foreach (glob("{$path}/*") as $file) {
            if (is_dir($file)) {
                $dir = $path . DS . $file;
                $this->deleteAllFromPath($dir);
                rmdir($dir);
            } else {
                unlink($file);
            }
        }
    }

    /**
     * Read data from a JSON file
     * @param string $path
     * @return stdClass
     */
    static public function readJson($path) {
        $content = file_get_contents($path);
        return json_decode($content);
    }

    /**
     * Write data to a JSON file
     * @param string $path
     * @param any $entity
     * @return boolean
     */
    static public function writeJson($path, $entity) {
        $json = json_encode($entity);
        return file_put_contents($path, $json);
    }

    /**
     * Read data from a PHP file
     * @param string $path
     * @return stdClass
     */
    static public function readPhp($path) {
        $__current_defined_vars = get_defined_vars();
        // include file
        include($path);
        $result = get_defined_vars();
        // remove prior variables
        foreach ($__current_defined_vars as $var => $value) {
            unset($result[$var]);
        }
        unset($result['__current_defined_vars']);
        // return as scalar
        if (array_key_exists('__data__', $result)) {
            return $result['__data__'];
        }
        // or convert to object
        else {
            $_result = new stdClass();
            foreach ($result as $key => $value) {
                $_result->$key = $value;
            }
            return $_result;
        }
    }

    /**
     * Write data to a PHP file
     * @example
     * // an object like:
     * //   {'a': 1, 'b': [1, 2, 3]}
     * // will output
     * <?php
     * $a = 1;
     * $b = array(
     *    1,
     *    2,
     *    3
     * );
     * ?>
     * @param string $path
     * @param any $entity
     * @return boolean
     */
    static public function writePhp($path, $entity) {
        $output = "<?php\n";
        // if necessary, convert to object
        if (is_scalar($entity)) {
            $temp = $entity;
            $entity = new stdClass();
            $entity->__data__ = $temp;
        }
        // write each key
        foreach ($entity as $key => $value) {
            $output .= self::writePhpKey($key, $value) . "\n";
        }
        $output .= "?>";
        // write to file
        return file_put_contents($path, $output);
    }

    /**
     * Create a PHP string representing the given $key and $value
     * @param any $key
     * @param any $value
     * @param int $_indent
     * @return string 
     */
    static public function writePhpKey($key, $value, $_indent = 0) {
        $indent = str_repeat("\t", $_indent);
        if (is_int($value)) {
            $format = '%s$%s = %d;';
        } elseif (is_float($value)) {
            $format = '%s$%s = %f;';
        } elseif (is_string($value)) {
            $value = addslashes($value);
            $format = '%s$%s = \'%s\';';
        } elseif (is_bool($value)) {
            $format = ($value) ? '%s$%s = true;' : '%s$%s = false;';
        } elseif (is_array($value)) {
            $output = sprintf('%s$%s = array( ', $indent, $key) . "\n";
            $_indent++;
            foreach ($value as $_key => $_value) {
                $line = self::writePhpKey($_key, $_value, $_indent);
                $line = substr($line, 0, -1) . ',' . "\n"; // replace comma for semi-colon
                $line = preg_replace('/\$([^ ]+) *=/', '\'$1\' =>', $line, 1); // replace = with =>
                $output .= $line;
            }
            $output .= sprintf('%s);', $indent);
            return $output;
        } elseif (is_object($value)) {
            $output = sprintf('%s$%s = new stdClass();', $indent, $key) . "\n";
            foreach ($value as $_key => $_value) {
                $output .= self::writePhpKey($key . '->' . $_key, $_value, $_indent) . "\n";
            }
            return $output;
        }
        // return
        return sprintf($format, $indent, $key, $value);
    }

    /**
     * Read a linux-style configuration file
     * @example 
     * // a .config file like:
     * etc = 42
     * [fruit]
     * apple = good
     * banana = false
     * 
     * // will return data like:
     * // {'etc': 42, 'fruit': {'apple': 'good', 'banana': false}} 
     * @param string $path
     * @return stdClass
     */
    static public function readConfig($path) {
        $result = new stdClass();
        $lines = file($path);
        $heading = false;
        foreach ($lines as $line) {
            // remove comment
            $line = preg_replace('/#.*$/', '', $line);
            // empty line
            if (preg_match('/^\s*$/m', $lines, $match)) {
                continue;
            }
            // heading change
            elseif (preg_match('/^\[(.*)\]\s*$/m', $lines, $match)) {
                $heading = $match[1];
                $result->$heading = new stdClass();
                continue;
            }
            // new key/pair
            elseif (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line);
                // get key
                $key = trim($key);
                // get value
                $value = trim($value);
                if (strpos($value, ',') !== false) {
                    $value = explode(',', $value);
                    array_walk($value, 'trim');
                }
                // set
                if ($heading)
                    $result->$heading->$key = $value;
                else
                    $result->$key = $value;
            }
        }
        return $result;
    }

    /**
     * Write a linux-style configuration file
     * @example
     * // an object like:
     * //   {'a': 1, 'b': [1, 2, 3]}
     * // will output
     * a = 1
     * b = [1, 2, 3]
     * @param string $path
     * @param any $entity
     * @return boolean
     */
    static public function writeConfig($path, $entity) {
        $output = '';
        $depth = self::getDepth($entity);
        // case: no headings needed
        if ($depth < 3) {
            foreach ($entity as $key => $value) {
                $output .= self::writeConfigKey($key, $value) . "\n";
            }
        }
        // case: headings needed
        else {
            // find top-level keys
            $scalar = array();
            $non_scalar = array();
            foreach ($entity as $key => $value) {
                if (is_scalar($value))
                    $scalar[] = $key;
                else
                    $non_scalar[] = $key;
            }
            // output
            foreach ($scalar as $key) {
                $output .= self::writeConfigKey($key, $entity->$key);
            }
            foreach ($non_scalar as $key) {
                $output .= "[$key]\n";
                $output .= self::writeConfigKey($key, $entity->$key);
                $output .= "\n";
            }
        }
        // write
        return file_put_contents($path, $output);
    }

    /**
     * Return array/object depth naively; see
     * http://stackoverflow.com/questions/262891
     * @param any $data
     * @return int
     */
    static public function getDepth($data) {
        $max_depth = 1;
        foreach ($data as $value) {
            if (is_array($value) || is_object($value)) {
                $depth = self::getDepth($value) + 1;
                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }
        return $max_depth;
    }

    /**
     * Writes a config key in linux-style format
     * @param string $key
     * @param any $value
     * @return string 
     */
    static public function writeConfigKey($key, $value) {
        if (is_array($value)) {
            // test if all keys are integers
            $keys = array_keys();
            $all_integer_test = true;
            foreach ($keys as $key) {
                if (!is_int($key)) {
                    $all_integer_test = false;
                    break;
                }
            }
            // case: numeric array
            if ($all_integer_test) {
                $output = sprintf('%s = %s', $key, implode(', ', $value)) . "\n";
            }
            // case: associative array
            else {
                $output = '';
                foreach ($value as $k => $v) {
                    $output .= self::writeConfigKey($key . '.' . $k, $v) . "\n";
                }
            }
        }
        // case: object
        elseif (is_object($value)) {
            $output = '';
            foreach ($value as $k => $v) {
                $output .= self::writeConfigKey($key . '.' . $k, $v) . "\n";
            }
        }
        // case: scalars
        else {
            $output = sprintf('%s = %s', $key, $value) . "\n";
        }
        // return
        return $output;
    }

    /**
     * Read a file's contents
     * @param string $path
     * @return string
     */
    static public function readRaw($path){
        return file_get_contents($path);
    }
    
    /**
     * Write anything to a file
     * @param string $path
     * @param any $entity
     * @return boolean
     */
    static public function writeRaw($path, $entity){
        return file_put_contents($path, (string) $entity);
    }
}