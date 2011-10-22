<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
abstract class AppObjectAbstract{

    /**
     * Name of property that holds the primary key
     * @var <string>
     */
    protected $__primary = 'id';

    /**
     * List of fields/properties
     * @var <array>
     */
    protected $__fields;

    /**
     * Whether action has changed the record
     * @var <boolean>
     */
    protected $__changed = false;

    /**
     * Actions
     */
    abstract public function exists();
    abstract public function enumerate();
    abstract public function create();
    abstract public function read();
    abstract public function update();
    abstract public function delete();

    /**
     * Prepare for incoming action
     */
    abstract public function __prepare();

    /**
     * Commit changes
     */
    abstract public function __commit();

    /**
     * Rollback changes on error
     */
    abstract public function __rollback();
    
    /**
     * Set unique identifier for object (normally in 'id' field)
     * @param <mixed> $id
     */
    public function __setID($id){
        $this->{$this->__primary} = $id;
    }

    /**
     * Get unique identifier for object
     * @return <mixed>
     */
    public function __getID(){
        return $this->{$this->__primary};
    }

    /**
     * Binds data to current Object
     * @param <mixed> $data Array/object
     * @return <AbstractObject>
     */
    public function __bind( $data ){
        if( is_null($data) ) return $this;
        if( is_array($data) ){ $data = (object) $data; }
        if( !is_object($data) ){
            throw new Exception('Could not load data', 400);
            return false;
        }
        // replace data into object
        foreach( $data as $property => $value ){
            if( property_exists($this, $property) ){ $this->$property = $value; }
        }
        // return
        return $this;
    }

    /**
     * Checks if the object has been changed
     * @return <bool>
     */
    public function __isChanged(){
        return $this->__changed;
    }

    /**
     * Get list of fields
     * @return <array>
     */
    protected function getFields(){
        if( !$this->__fields ){
            $fields = array_keys(get_public_vars($this));
            $this->setFields($fields);
        }
        return $this->__fields;
    }

    /**
     * Set list of fields and setup the object
     * @param <array> $list
     * @return <object>
     */
    protected function setFields( $list ){
        if( !is_array($list) ){ throw new Exception('Could not set fields', 500); }
        $this->__fields = $list;
        foreach( $list as $field ){
            if( $this->isEmpty($field) ){
                $this->$field = null;
            }
        }
        return $this;
    }

    /**
     * Auto-fills an empty field
     * @param <string> $field
     * @return <bool> True, if field was changed
     */
    protected function autoFill($field, $creation = false){
        $changed = false;
        switch($field){
            case 'created':
                if( $creation ) $this->created = date('Y-m-d H:i:s');
                $changed = true;
            break;
            case 'modified':
                $this->modified = date('Y-m-d H:i:s');
                $changed = true;
            break;
        }
        return $changed;
    }



    /**
     * Checks if a field is empty/exists
     * @param <string> $field
     * @return <bool>
     */
    protected function isEmpty($field){
        if( !isset($this->$field) ) return true;
        if( is_null($this->$field) ) return true;
        return false;
    }

    /**
     * Get cache key for this object
     * @return <string>
     */
    protected function getCacheKey(){
        return get_class($this).'-'.$this->__getID();
    }

    /**
     * Get cache key for this object listing
     * @return <string>
     */
    protected function getEnumerateCacheKey(){
        $class = get_class($this);
        $inflection = new Inflection($class);
        return $inflection->toPlural()->toString();
    }

    /**
     * Clears public property/value pairs
     * @return <Object>
     */
    protected function clear(){
        foreach( $this->getFields() as $field ){
            $this->$field = null;
        }
        // return
        return $this;
    }

    /**
     * List of public property/value pairs
     * @return <array>
     */
    protected function toArray(){
        $out = array();
        foreach( $this->getFields() as $field ){
            $out[ $field ] = $this->$field;
        }
        return $out;
    }

    /**
     * Generic clone of current instance
     * @return <stdClass>
     */
    protected function toClone(){
        $out = new stdClass;
        foreach( $this->getFields() as $field ){
            $out->$field = $this->$field;
        }
        return $out;
    }

    /**
     * JSON list of public property/value pairs
     * @return <string>
     */
    protected function toJson(){
        return json_encode( $this->toArray() );
    }

}