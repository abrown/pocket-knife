<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */

/**
 * Provides methods for creating an HTML-formatted documentation
 * page for a class.
 * @example
 * $doc = new BasicDocumentation('BasicScheduler');
 * echo $doc->getHtml();
 * 
 * // alternately, use BasicClass
 * echo BasicClass:getDocumentation('BasicScheduler');
 * @uses BasicClass, WebTemplate, WebUrl
 */
class BasicDocumentation {

    /**
     * HTML page format.
     * @var string
     */
    public $template = '
	<div class="doc-section doc-head">
		<h2><template:class/></h2>
		<p><template:class_description/></p>
		<p>Uses these classes: <code><template:class_dependencies/></code></p>
	</div>
	<div class="doc-section doc-body">
		<h3>Properties</h3>
		<table><template:properties/></table>
		<h3>Methods</h3>
		<table><template:methods/></table>
	</div>
	<div class="doc-section doc-code">
		<h3>Code (<code><template:filename/></code>)</h3>
		<pre><code><template:code/></code></pre>
	</div>';

    /**
     * HTML list format; used by properties and methods.
     * @var string
     */
    public $template_item = '<tr><td><template:title/></td><td><template:description/></td></tr>';

    /**
     * Stores the ReflectionClass for this documentation object
     * @var ReflectionClass
     */
    private $class;

    /**
     * Stores filename to this class for this documentation object
     * @var unknown_type
     */
    private $filename;

    /**
     * Constructor
     * @param string $class the name of a valid, autoloadable class.
     */
    function __construct($class) {
        if (!class_exists($class, false)) {
            autoload($class);
        }
        // check existence
        if (!class_exists($class))
            throw new Error('Cannot find class ' . $class, 404);
        // get filename
        $replaced = preg_replace('/([a-z])([A-Z])/', '$1/$2', $class);
        $replaced = str_replace('/', DS, $replaced);
        $this->filename = get_base_dir() . DS . $replaced . '.php';
        // create reflection
        $this->class = new ReflectionClass($class);
    }

    /**
     * Return the ReflectionClass of current class
     * @return ReflectionClass
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * Return HTML-formatted description of class
     * @return string
     */
    public function getHtml() {
        $template = new WebTemplate($this->template, WebTemplate::STRING);
        // set Tidy
        $template->tidy = true;
        // do replacements
        $template->replace('class', $this->getClass()->getName());
        $template->replace('class_dependencies', $this->getDependenciesHtml());
        $template->replace('class_description', $this->getDescriptionHtml($this->class));
        //$template->replace('class_example', $this->getExampleHtml());
        $template->replace('properties', $this->getPropertiesHtml());
        $template->replace('methods', $this->getMethodsHtml());
        $template->replace('filename', $this->filename);
        $template->replace('code', $this->getCodeHtml());
        // return
        return $template->toString();
    }

    /**
     * Return HTML-formatted description of class; alias of getHtml()
     * @return string
     */
    public function __toString() {
        return $this->getHtml();
    }

    /**
     * Return an HTML-formatted list of links to classes this class uses
     * @return string
     */
    private function getDependenciesHtml() {
        $dependencies = BasicClass::findDependencies($this->getClass()->getName());
        sort($dependencies);
        // format each dependency
        $html = array();
        foreach ($dependencies as $dependency) {
            if ($dependency == $this->getClass()->getName())
                continue; // skip this class
            $url = WebUrl::getLocationUrl() . '/' . $dependency;
            $html[] = sprintf('<a href="%s">%s</a>', $url, $dependency);
        }
        // return
        return implode(', ', $html);
    }

    /**
     * Return an HTML-formatted description of an object as described in PHPDoc
     * @param ReflectionObject $reflection_object
     * @return string
     */
    public function getDescriptionHtml($reflection_object) {
        $annotations = $this->parseDocString($reflection_object->getDocComment());
        $html = array();
        // compile annotations
        foreach ($annotations as $a) {
            switch ($a['annotation']) {
                case 'description':
                    $html[] = sprintf('<p>%s</p>', trim($a['text']));
                    break;
                case '@link':
                    $html[] = sprintf('<p>Link: <a href="%s">%s</a></p>', trim($a['text']), trim($a['text']));
                    break;
                case '@example':
                    $html[] = sprintf('<p>Example: </p><pre><code>%s</code></pre>', $a['text']);
                    break;
                case '@deprecated':
                    $html[] = sprintf('<p><b>Deprecated:</b> %s</p>', trim($a['text']));
                    break;
                case '@var':
                    $parts = explode(' ', trim($a['text']), 2);
                    $type = $parts[0];
                    $description = (isset($parts[1])) ? $parts[1] : '';
                    if (!$description)
                        break;
                    $html[] = sprintf('<p>Property is %s</p>', $description);
                    break;
                case '@return':
                    $parts = explode(' ', trim($a['text']), 2);
                    $type = $parts[0];
                    $description = (isset($parts[1])) ? $parts[1] : '';
                    if (!$description)
                        break;
                    $html[] = sprintf('<p>Return %s</p>', $description);
                    break;
                case '@param':
                    $parts = explode(' ', trim($a['text']), 3);
                    $type = $parts[0];
                    $name = (isset($parts[1])) ? $parts[1] : '';
                    $description = (isset($parts[2])) ? $parts[2] : '';
                    if (!$description)
                        break;
                    $html[] = sprintf('<p>Parameter %s is %s</p>', $name, $description);
                    break;
                case '@uses':
                    // do nothing with these; handled elsewhere
                    break;
            }
        }
        // return
        return implode('', $html);
    }

    /**
     * Return an HTML-formatted property list
     * @return string
     */
    public function getPropertiesHtml() {
        $properties = $this->getClass()->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_STATIC);
        if (count($properties) < 1) {
            $template = new WebTemplate($this->template_item, WebTemplate::STRING);
            $template->replace('title', 'No public properties found.');
            return $template->toString();
        }
        $html = array();
        foreach ($properties as $property) {
            // do templating
            $template = new WebTemplate($this->template_item, WebTemplate::STRING);
            // create title
            $title = sprintf('%s%s <i>%s</i> <b>%s</b>', $property->isPublic() ? 'public' : '', $property->isStatic() ? ' static' : '', $this->getPropertyType($property), $property->getName());
            $template->replace('title', $title);
            // create description
            $template->replace('description', $this->getDescriptionHtml($property));
            // finish
            $html[] = $template->toString();
        }
        // return
        return implode('', $html);
    }

    /**
     * Return the property type as described by '@var' annotation
     * @param ReflectionProperty $property
     * @return string
     */
    private function getPropertyType($property) {
        $annotations = $this->parseDocString($property->getDocComment());
        foreach ($annotations as $a) {
            if ($a['annotation'] == '@var')
                return $a['text'];
        }
        // else
        return 'unknown';
    }

    /**
     * Return an HTML-formatted method list
     * @return string
     */
    public function getMethodsHtml() {
        $html = array();
        foreach ($this->getClass()->getMethods() as $method) {
            // do not display private/protected items
            if (!$method->isPublic() && !$method->isStatic())
                continue;
            // do templating
            $template = new WebTemplate($this->template_item, WebTemplate::STRING);
            // create title
            $title = sprintf('%s%s <i>%s</i> <b>%s</b>(%s)', $method->isPublic() ? 'public' : '', $method->isStatic() ? ' static' : '', $this->getMethodReturnType($method), $this->getMethodName($method), $this->getMethodParameterTypes($method)
            );
            $template->replace('title', $title);
            // create description
            $template->replace('description', $this->getDescriptionHtml($method));
            // finish
            $html[] = $template->toString();
        }
        // return
        return implode('', $html);
    }

    /**
     * Return an HTML-formatted method name; strikes through deprecated methods
     * @param type $method
     * @return type 
     */
    private function getMethodName($method) {
        $annotations = $this->parseDocString($method->getDocComment());
        foreach ($annotations as $a) {
            if ($a['annotation'] == '@deprecated') {
                return '<s>' . $method->getName() . '</s>';
            }
        }
        // else
        return $method->getName();
    }

    /**
     * Return an HTML-formatted method type; defaults to 'null'
     * @param ReflectionMethod $method
     * @return string
     */
    private function getMethodReturnType($method) {
        $annotations = $this->parseDocString($method->getDocComment());
        foreach ($annotations as $a) {
            if ($a['annotation'] == '@return') {
                list($type, ) = explode(' ', trim($a['text']));
                return $type;
            }
        }
        // else
        return 'null';
    }

    /**
     * Return an HTML-formatted list of method parameters with their types
     * @param ReflectionMethod $method
     * @return string
     */
    private function getMethodParameterTypes($method) {
        $annotations = $this->parseDocString($method->getDocComment());
        $html = array();
        foreach ($annotations as $a) {
            if ($a['annotation'] == '@param') {
                list($type, $name, ) = explode(' ', trim($a['text']));
                $html[] = $type . ' ' . $name;
            }
        }
        // return
        return implode(', ', $html);
    }

    /**
     * Return highlighted code of file
     * @return string 
     */
    private function getCodeHtml() {
        $code = file_get_contents($this->filename);
        if (!$code)
            $code = "Could not locate {$this->filename}";
        $code = str_replace("\r\n", "\n", $code);
        return highlight_string($code, true);
    }

    /**
     * Parse PHPDoc into array
     * @param string $doc_string
     * @return array a list of annotations in the form:
     * [ 'annotation' => 'description', 'text' => '...'],
     * [ 'annotation' => '@param', 'text' => '...'],
     * ...
     */
    public function parseDocString($doc_string) {
        $start = strpos($doc_string, '/**');
        if ($start !== false) {
            $doc_string = substr($doc_string, $start + 2);
        }
        $end = strrpos($doc_string, '*/');
        if ($end !== false) {
            $doc_string = substr($doc_string, 0, $end - 1);
        }
        // split
        $lines = preg_split('/\n/', $doc_string);
        // setup annotations
        $annotations = array(array('annotation' => 'description', 'text' => ''));
        // regex
        foreach ($lines as $line) {
            if (preg_match('/\* (@\w+) ?(.+)?/', $line, $matches)) {
                $annotations[] = array('annotation' => @$matches[1], 'text' => trim(@$matches[2]));
                end($annotations);
            } else {
                $line = preg_replace('/\s*\*/', '', $line, 1);
                $line = rtrim($line, " \t\n\r\0\x0B");
                if ($annotations[key($annotations)]['text'])
                    $annotations[key($annotations)]['text'] .= "\n";
                $annotations[key($annotations)]['text'] .= $line;
            }
        }
        // return
        return $annotations;
    }

}