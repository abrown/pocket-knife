<?php

/**
 * An example resource
 */
class Dog extends Animal {

    public $gender = 'male';

    public function __construct($name = null, $color = null, $age = null) {
        $name = ($name === null) ? 'Spike' : $name; // default name
        parent::__construct($name, $color, $age);
    }

    public function getURI() {
        return 'dog';
    }

    public function GET_OUTPUT_TRIGGER(Representation $representation) {
        $representation->getData()->age = 100;
        unset($representation->getData()->gender);
    }

    public function GET() {
        return $this;
    }

    public function PUT($entity, $id) {
        foreach ($entity as $key => $value) {
            $this->$key = $value;
        }
        $this->changed();
    }

    public function POST($entity) {
        return $entity;
    }

}
