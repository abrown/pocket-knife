<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 *
 * The service is instantiated. Routing is set up. The object is decided upon.
 * An action is found. Data is input into the action in the specified format.
 * Data is sent from the action in the specified format.
 * 
 * @example
 *
    require '/home5/casabrow/public_html/pocket-knife/start.php';
    Settings::setPath('Settings.php');

    $app = new App();
    $app->setAllowedObjects('data', 'files', 'changes');

    // routing
    try{
        $object = Routing::getToken('object');
    }
    catch(Exception $e){
        $object = '';
    }

    // allowed actions
    if( $object == 'files' ){
         $app->setAllowedActions(
                'upload',
                'download',
                'sync',
                'poll',
                'deleteAll',
                'exists',
                'enumerate',
                'create',
                'read',
                'update',
                'delete',
                'with'
          );
    }
    else{
        $app->setAllowedActions(
                'exists', 
                'enumerate',
                'create',
                'read',
                'update',
                'delete'
         );
    }

    // allowed input
    //if( Routing::getToken('action') == 'upload' ) $app->setInputFormat('Xml');
    $app->setInputFormat('Html');

    if( $object == 'changes' ) $app->setOutputFormat('Json');
    else $app->setOutputFormat('Html');

    $app->execute();

 * @endexample
 */
class App{

    /**
     * Allowed objects
     * E.g.: A list of web objects/endpoints such as 'products', 'customers', etc.
     * These are reduced to their class name with Inflection
     * @var <array>
     */
    private $allowed_objects = array();

    /**
     * Allowed actions
     * A list of actions that are allowed on an entity. All enabled by default.
     * @var <array>
     */
    private $allowed_actions = array('exists', 'enumerate', 'create', 'read', 'update', 'delete');

    /**
     * Holds an AbstractAction instance
     * @var <AbstractAction>
     */
    private $action;

    /**
     * Instance of an AppView that formats/prepares incoming data
     * @var <AppViewAbstract>
     */
    private $input;

    /**
     * Instance of an AppView that formats/prepares outgoing data
     * @var <AppViewAbstract>
     */
    private $output;

    /**
     * Sets allowed objects
     * @param <mixed>, ...
     */
    public function setAllowedObjects(){
        $arguments = func_get_args();
        if( !empty($arguments) ){
            $this->allowed_objects = array_merge($this->allowed_objects, $arguments);
        }
    }

    /**
     * Checks whether the given object is allowed
     * @param <string> $object
     * @return <bool>
     */
    public function isObjectAllowed($object){
        return in_array($object, $this->allowed_objects);
    }

    /**
     * Sets allowed actions
     * @param <mixed>, ...
     */
    public function setAllowedActions(){
        $arguments = func_get_args();
        if( !empty($arguments) ){
            $this->allowed_actions = array_merge($this->allowed_actions, $arguments);
        }
    }

    /**
     * Checks whether the given entity is allowed
     * @param <string> $entity
     * @return <bool>
     */
    public function isActionAllowed($action){
        return in_array($action, $this->allowed_actions);
    }

    /**
     * Get AbstractAction instance
     * @return <KAbstractAction>
     */
    protected function getAction(){
        if( !$this->action ){
            $this->action = new AppAction(Routing::getClassname());
        }
        return $this->action;
    }

    /**
     * Set input instance
     * @param <string> $type
     */
    public function setInputFormat($type){
        if( !class_exists($type, false) ) $type = 'AppFormat'.ucfirst($type);
        $this->input = new $type();
    }

    /**
     * Get input instance
     * @return <KAbstractInput>
     */
    public function getInput(){
        if( !is_object($this->input) ){
            $this->input = new AppViewHtml();
        }
        return $this->input;
    }

    /**
     * Set output instance
     * @param <string> $view
     */
    public function setOutputFormat($type){
        if( !class_exists($type, true) ) $type = 'AppFormat'.ucfirst($type);
        $this->output = new $type;
    }

    /**
     * Get output instance
     * @return <KAbstractOutput>
     */
    public function getOutput(){
        if( !is_object($this->output) ){
            $this->output = new AppViewHtml();
        }
        return $this->output;
    }

    /**
     * Handles requests, creates instances, and returns result
     */
    public function execute(){
        // what we act upon
        $object = Routing::getToken('object');
        $action = Routing::getToken('action');
        $id = Routing::getToken('id');
        try{
            // allowed?
            if( !$this->isObjectAllowed($object) )
                    throw new Exception('Object is not allowed', 403);
            if( !$this->isActionAllowed($action) )
                    throw new Exception('Action is not allowed', 403);
            // set id, if necessary
            if( $id )
                $this->getAction()->getObject()->__setID($id);
            // get data
            $in = $this->getInput()->getData();
            // consume data
            $out = $this->getAction()->getResult($action, $in);
            // return result
            $this->getOutput()->setData($out);
            $this->getOutput()->send();
        }
        catch(Exception $e){
            $this->getOutput()->setData($e);
            $this->getOutput()->error();
        }
    }
}