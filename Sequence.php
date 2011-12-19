<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Sequence
 * @uses WebSession, WebRouting, WebHttp
 */
class Sequence {

    /**
     * URIs for the steps in this sequence
     * @var array 
     */
    public $steps = array();
    
    /**
     * Template to apply to the output after processing 
     * @var string 
     */
    public $template;
    
    /**
     * Set in WebSession (not in Settings), this property marks what step the user is currently at
     * @var int 
     */
    public $current_step;
    
    /**
     * Constructor
     * @param Settings $settings 
     */
    public function __construct( $settings ){
        // determines what settings must be passed
        $settings_template = array(
            'steps' => Settings::MANDATORY | Settings::MULTIPLE,
            'template' => Settings::OPTIONAL | Settings::PATH
        );
        // accepts settings
        if (!$settings || !is_a($settings, 'Settings'))
            throw new ExceptionSettings('Incorrect settings given.', 500);
        $settings->validate($settings_template);
        // copy settings into this object
        foreach ($this as $key => $value) {
            if (isset($settings->$key))
                $this->$key = $settings->$key;
        }
        // setup session 
        if( WebSession::get('sequence-current-step') )
            $this->step = Session::get('sequence-current-step');
        else {
            $this->current = 1;
            Session::put('sequence-current-step', 1);
        }
    }
    
    /**
     * Returns this object's URI
     * @return string
     */
    public function getURI(){
        $_GET['step'] = $this->current_step;
        return WebRouting::createUrl('');
    }
    
    public function getStepURI( $step ){
        // 
        $_url = $this->steps[$this->current_step - 1];
        // case: server name defined 
        if( strpos('http', $_url) === 0 ) return $_url;
        // default: use this server name
        $url = 'http://'.$_SERVER['SERVER_NAME'].'/'.$_url;
        return $url;  
    }
    
 
    public function execute(){
        // check for step existence
        if( !array_key_exists($this->current_step - 1, $this->steps) ) throw new ExceptionSequence('No URI exists for this step', 404);
        // check URL injection attempt
        if( $_GET['step'] != $this->current_step ) WebHttp::redirect( $this->getURI() );
        // get URL
        $url = $this->getStepURI( $this->current_step );
        // proxy
        if( $_SERVER['REQUEST_METHOD'] == 'GET' ){
            echo WebHttp::request($url, 'GET'); // a GET request to the sequence returns the GET from the step URL
        }
        elseif( $_SERVER['REQUEST_METHOD'] == 'POST' ){
            $result = WebHttp::request($url, 'POST', urlencode($_POST), 'application/x-www-form-urlencoded'); // a POST request to the sequence returns the POST from the step URL so we can validate the response
            if( WebHttp::getCode() == 200 ){
                $_GET['message'] = 'Step was completed successfully';
                $this->current_step++;
                WebSession::put('sequence-current-step', $this->current_step);
                WebHttp::redirect( $this->getURI() );
            }
            else{
                $_GET['message'] = 'Step failed';
                WebHttp::redirect( $this->getURI() );
            }
        }
        else{
            throw new ExceptionSequence('Only GET and POST methods are allowed in a sequence', 400);
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