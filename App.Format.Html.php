<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppFormatHtml implements AppFormatInterface{

    /**
     * @var <mixed> holds data to output
     */
    private $out;

    /**
     * Get input data
     * @return <mixed>
     */
    public function getData(){
        $key = Routing::getClassname();
        if( !isset($_REQUEST[$key]) ) return null;
        if( get_magic_quotes_gpc() ){ $_REQUEST[$key] = $this->unescape($_REQUEST[$key]); }
        return $_REQUEST[$key];
    }

    /**
     * Recursively string unnecessary slashes
     * @param <mixed> $thing
     * @return <mixed>
     */
    private function unescape($thing){
        if( is_array($thing) ){
            foreach($thing as $key => $value){
                $key = $this->unescape($key);
                $value = $this->unescape($value);
                $new[$key] = $value;
            }
            return $new;
        }
        elseif( is_string($thing) ) return stripslashes($thing);
        else return $thing;
    }

    /**
     * Set output data
     * @param <mixed>
     */
    public function setData($data){
        $this->out = $data;
    }

    /**
     * Send formatted output data
     */
    public function send(){
        if( !headers_sent() ) header('Content-Type: text/html');
        $toHtml = Routing::getToken('action').'ToHtml';
        $template = $this->$toHtml($this->out);
        $template->display();
    }

    /**
     * Send formatted error data
     */
    public function error(){
        $error = $this->out;
        // send HTTP header
        if( !headers_sent() ){
            header($_SERVER['SERVER_PROTOCOL'].' '.$error->getCode());
            header('Content-Type: text/html');
        }
        // display error
        $template = $this->getTemplate();
        // make title
        $title = $error->getCode().' Error';
        $template->replace('title', $title);
        // make body
        $body = '<p><i>Message</i>: '.$error->getMessage().'</p>';
        $body .= '<p><i>Code</i>: '.$error->getCode().'</p>';
        $template->replace('body', $body);
        // display
        $template->display();
    }

    /**
     * Returns basic HTML template
     * @return <KTemplate> Template HTML
     */
    public function getTemplate(){
        static $template = null;
        if( $template === null ){
            $template = new Template( $this->template );
        }
        return $template;
    }

    /**
     * Basic HTML Template
     * @var <string>
     */
    protected $template = '
        <!DOCTYPE html>
        <html>
            <head>
                <title><K:title/></title>
                <K:css/>
            </head>
            <body>
                <h1><K:title/></h1>
                <K:body/>
            </body>
        </html>
    ';

    /**
     * Determines whether a field should have a disabled attribute
     * @return <boolean> disabled
     */
    protected function isDisabled($field){
        return in_array($field, $this->disabled_inputs);
    }

    /**
     * Disabled inputs
     * @var <array>
     */
    protected $disabled_inputs = array(
        'id',
        'created',
        'modified' 
    );

    /**
     * Gets base link for HTML forms
     * @return <string> URL
     */
    public function getLink(){
        static $link = null;
        if( $link === null ){
            $inflect = new Inflection( Routing::getName() );
            $inflected = $inflect->toPlural()->toUnderscoreStyle()->toLowerCase()->toString();
            $link = Routing::getAnchoredUrl().'/'.$inflected;
        }
        return $link;
    }

    /**
     * Returns HTML version of exists call
     * @param <AbstractObjecty> $instance of class serviced
     * @param <boolean> $this->dataxists
     * @return <KTemplate> HTML
     */
    protected function existsToHtml($exists){
        $template = $this->getTemplate();
        // make title
        $title = Routing::getName().' #'.Routing::getToken('id');
        $template->replace('title', $title);
        // make body
        $body = '<a href="'.$this->getLink().'">List All</a> ';
        $body = ($exists) ? '<p>Exists</p>' : '<p>Does not exist</p>';
        $template->replace('body', $body);
        // return
        return $template;
    }

    /**
     * Returns HTML version of enumerate call
     * @param <AbstractObject> $instance of class serviced
     * @param <array> $list of objects
     * @return <string> HTML
     */
    public function enumerateToHtml($list){
        $template = $this->getTemplate();
        // make title
        $pluralize = new Inflection( Routing::getName() );
        $title = $pluralize->toPlural()->toString();
        $template->replace('title', $title);
        // make body
        $body = '<a href="'.$this->getLink().'/new/create">New</a>';
        $body .= '<table><tr>';
        if( array_key_exists(0, $list) ){
            foreach($list[0] as $key => $value ){
                $body .= '<th>'.$key.'</th>';
            }
            $body .= '<th></th>';
        }
        else{
            $body .= '<td>No items found</td>';
        }
        $body .= '</tr>';
        foreach($list as $item){
            $body .= '<tr>';
            foreach($item as $key => $value ){
                if( !is_scalar($value) ) $value = '...';
                if( strlen($value) > 100 ) $value = substr($value, 0, 100);
                $body .= '<td>'.htmlentities($value).'</td>';
            }
            // action links
            $id = $item->id;
            $body .= '<td><a href="'.$this->getLink().'/'.$id.'/read">View</a> ';
            $body .= '<a href="'.$this->getLink().'/'.$id.'/update">Edit</a> ';
            $body .= '<a href="'.$this->getLink().'/'.$id.'/delete">Delete</a></td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
        $template->replace('body', $body);
        // return
        return $template;
    }

    /**
     * Returns HTML version of create call
     * @param <AbstractObject> $instance of class serviced
     * @param <boolean> $created
     * @return <string> HTML
     */
    protected function createToHtml($id){
        $template = $this->getTemplate();
        // options: created or not
        if( $id ){
            // make title
            $title = Routing::getName().' #'.$id.' Created Successfully';
            $template->replace('title', $title);
            // make body
            $body = '<a href="'.$this->getLink().'">List All</a> ';
            $body .= '<a href="'.$this->getLink().'/'.$id.'/read">View</a> ';
            $body .= '<a href="'.$this->getLink().'/'.$id.'/update">Edit</a> ';
            $body .= '<a href="'.$this->getLink().'/'.$id.'/delete">Delete</a>';
            $template->replace('body', $body);
        }
        else{
            // make title
            $title = 'New '.Routing::getName();
            $template->replace('title', $title);
            // make body
            $body = '<a href="'.$this->getLink().'">List All</a> ';
            $body .= '<form method="POST" action="'.$this->getLink().'/new/create"><table>';
            // get object fields
            $classname = Routing::getClassname();
            $object = new $classname();
            foreach($object as $key => $value){
                $body .= '<tr>';
                $body .= '<td>'.htmlentities($key).'</td>';
                $_key = Routing::getName().'['.$key.']';
                $_disabled = $this->isDisabled($key) ? 'disabled="disabled" ' : '';
                $body .= '<td><input type="text" name="'.$_key.'" value="" '.$_disabled.'/></td>';
                $body .= '</tr>';
            }
            $body .= '</table><input type="submit" value="Save"/></form>';
            $template->replace('body', $body);
        }
        // return
        return $template;
    }

    /**
     * Returns HTML version of read call
     * @param <AbstractObject> $instance of class serviced
     * @param <object> $item
     * @return <string> HTML
     */
    protected function readToHtml($item){
        $template = $this->getTemplate();
        // make title
        $title = Routing::getName().' #'.Routing::getToken('id');
        $template->replace('title', $title);
        // make body
        $id = Routing::getToken('id');
        $body = '<a href="'.$this->getLink().'">List All</a> ';
        $body .= '<a href="'.$this->getLink().'/'.$id.'/update">Edit</a> ';
        $body .= '<a href="'.$this->getLink().'/'.$id.'/delete">Delete</a>';
        $body .= '<table>';
        foreach($item as $key => $value ){
            $body .= '<tr>';
            $body .= '<td>'.htmlentities($key).'</td>';
            $body .= '<td>'.htmlentities($value).'</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
        $template->replace('body', $body);
        // return
        return $template;
    }

    /**
     * Returns HTML version of update call
     * @param <AbstractObject> $instance of class serviced
     * @param <boolean> $updated
     * @return <string> HTML
     */
    private function updateToHtml($item){
        $template = $this->getTemplate();
        // make title
        $id = Routing::getToken('id');
        $title = Routing::getName().' #'.$id;
        $template->replace('title', $title);
        // make body
        $body = '<a href="'.$this->getLink().'">List All</a> ';
        $body .= '<a href="'.$this->getLink().'/'.$id.'/read">View</a> ';
        $body .= '<a href="'.$this->getLink().'/'.$id.'/delete">Delete</a>';
        if( $item->__isChanged() ){
            $body .= '<p>Updated successfully.</p>';
        }
        else{
            $body .= '<form method="post" action="'.$this->getLink().'/'.$id.'/update"><table>';
            foreach($item as $key => $value ){
                $body .= '<tr>';
                $body .= '<td>'.htmlentities($key).'</td>';
                $_key = Routing::getName().'['.$key.']';
                $_type = $this->isDisabled($key) ? 'hidden' : 'text';
                $body .= '<td><input type="'.$_type.'" name="'.$_key.'" value="'.$value.'" /></td>';
                $body .= '</tr>';
            }
            $body .= '</table><input type="submit" value="Save"/></form>';
        }
        $template->replace('body', $body);
        // return
        return $template;
    }

    /**
     * Returns HTML version of delete call
     * @param <AbstractObject> $instance of class serviced
     * @param <boolean> delete
     * @return <string> HTML
     */
    private function deleteToHtml($deleted){
        $template = $this->getTemplate();
        // make title
        $title = Routing::getName().' #'.Routing::getToken('id');
        $template->replace('title', $title);
        // make body
        $body = '<a href="'.$this->getLink().'">List All</a> ';
        $body .= ($deleted) ? '<p>Deleted successfully.</p>' : '<p>Failed to delete.</p>';
        $template->replace('body', $body);
        return $template;
    }

    /**
     * Default handler for uncreated HTML method
     * @param <string> $name
     * @param <mixed> $data
     */
    private function __call($name, $data){
        $template = $this->getTemplate();
        // make title
        $pluralize = new Inflection( Routing::getName() );
        $title = $pluralize->toPlural()->toString();
        $template->replace('title', $title);
        // make body
        $body = '<p>The method <code>'.$name.'</code> has no HTML Format defined. Please create one.</p> ';
        $body .= '<code>Data: '.print_r($data, true).'</code>';
        $template->replace('body', $body);
        return $template;
    }
}