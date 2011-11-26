<?php

/**
 * 
 * Use _GET for output
 * Use _POST for input
 */
class Sequence {

    private $steps = array();
    private $parameter = 'step';
    private $current;

    public function __construct() {
        if (Session::get('current'))
            $this->current = Session::get('current');
        else {
            $this->current = 0;
            Session::put('current', 0);
        }
    }

    public function addStep($id, $output_script, $process_script = null, $requirements = null, $data = null) {
        $this->steps[] = new Step($id, $output_script, $process_script, $requirements, $data);
    }

    public function getStep($index) {
        if (array_key_exists($index, $this->steps))
            return $this->steps[$index];
        else
            throw new Exception('Attempted to access a non-existent step', 400);
    }
    
    public function getStepById($id){
        foreach($this->steps as $i => $step){
            if( $id == $step->getId() ){
                return $step;
            }
        }
        return null;
    }

    public function getCurrentStep() {
        return $this->getStep($this->current);
    }

    public function setParameter($parameter) {
        $this->parameter = $parameter;
    }

    public function next() {
        // put to session
        $this->current++;
        Session::put('current', $this->current);
        // redirect
        Http::redirect($this->getUrl());
    }

    public function clear($index = null) {
        if ($index !== null)
            $this->getStep($index)->setData(null);
        else {
            foreach ($this->steps as &$step) {
                $step->setData(null);
            }
        }
    }

    public function getUrl() {
        if (strpos(Http::getUrl(), '?') !== false)
            $url = substr(Http::getUrl(), 0, strpos(Http::getUrl(), '?'));
        else
            $url = Http::getUrl();
        $url .= '?' . $this->parameter . '=' . $this->getCurrentStep()->getId();
        return $url;
    }

//    public function loadData() {
//        $data = Session::get('step-data');
//        if (is_array($data)) {
//            foreach ($data as $i => $d) {
//                $this->getStep($i)->setData($d);
//            }
//        }
//    }
//
//    public function saveData() {
//        $data = array();
//        foreach ($this->steps as $step) {
//            $data[] = $step->getData();
//        }
//        Session::put('step-data', $data);
//    }

    public function execute() {
        // check current marker
        if (!array_key_exists($this->parameter, $_GET)) {
            $this->clear();
            Session::put('current', 0);
            Http::redirect($this->getUrl());
        }
        // step output
        if (empty($_POST)) {
            require $this->getCurrentStep()->getOutputScript();
        }
        // step input
        else {
            // bind data
            $this->getCurrentStep()->setData($_POST);
            // check data
            if ($this->getCurrentStep()->isValid()) {
                if ($this->getCurrentStep()->getProcessScript()){
                    try{
                        require $this->getCurrentStep()->getProcessScript();   
                    }
                    catch(Exception $e){
                        $this->getCurrentStep()->addError($e);
                        require $this->getCurrentStep()->getOutputScript();
                        exit();
                    }
                }
                $this->next();
            }
            else {
                //$errors = $this->getCurrentStep()->getErrors();
                require $this->getCurrentStep()->getOutputScript();
            }
        }
    }

}

/**
 * Step
 */
class Step {

    private $id;
    private $output_script;
    private $process_script;
    private $requirements;
    private $data;
    private $errors;

    function __construct($id, $output_script, $process_script, $requirements, $data = null) {
        $this->id = $id;
        $this->output_script = $output_script;
        $this->process_script = $process_script;
        $this->requirements = $requirements;
        $this->data = $data;
    }

    public function getProcessScript() {
        return $this->process_script;
    }

    public function setProcessScript($process_script) {
        $this->process_script = $process_script;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getOutputScript() {
        return $this->output_script;
    }

    public function setOutputScript($output_script) {
        $this->output_script = $output_script;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function isValid() {
        if (is_null($this->requirements))
            return true;
        $v = new Validation();
        foreach ($this->requirements as $rule) {
            if (count($rule) !== 3)
                throw new Exception('Cannot add invalid rule', 400);
            $v->addRule($rule[0], $rule[1], $rule[2]);
        }
        $data = Set::flatten($this->data);
        $this->errors = $v->validateList($data);
        return ($this->errors) ? false : true;
    }

    public function addError($error){
        $this->errors[] = $error;
    }
    
    public function getErrors() {
        return $this->errors;
    }

}