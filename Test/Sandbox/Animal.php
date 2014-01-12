<?php

class Animal extends Resource {

    public $name;
    public $color;
    public $age;
    protected $storage = array('type' => 'memory');

    function __construct($name = null, $color = null, $age = null) {
        $this->name = $name;
        $this->color = $color;
        $this->age = $age;
    }

    public function getURI() {
        return 'animal';
    }

    public function GET() {
        return $this;
    }

    public function __toString() {
        return "A {$this->color} animal called {$this->name} is {$this->age}.";
    }

    public function PUT($entity, $id) {
        foreach ($entity as $key => $value) {
            $this->$key = $value;
        }
        $this->changed();
    }
    
    public function DELETE(){
        return true;
    }

}
