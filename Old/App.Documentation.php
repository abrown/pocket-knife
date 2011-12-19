<?php
/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
class AppDocumentation{
    
    /**
     * Formats
     */
    public $head = '<h2>%s</h2><p>%s</p>';
    public $list = '<ul class="Documentor">%s</ul>';
    public $properties = '<li><h4>%s</h4><dl>%s</dl></li>';
    public $methods = '<li><h4>%s</h4><dl>%s</dl></li>';
    public $definition = '<dt>%s</dt><dd>%s</dd>';

    /**
     * Maps '@var' tokens to human-readable labels
     * @var <array>
     */
    public $map = array(
        'var' => 'Type'
    );

    /**
     * Characters to trim
     * @var <string>
     */
    private $unwanted = " \t\n\r\0\x0B*";

    /**
     * Cache
     * @var <array>
     */
    private $classes;

    /**
     * Constructor
     * @param <string> $file Path
     */
    function __construct( $class = null ){
        if( $class ){
            $this->load( $class );
        }
    }

    /**
     * Loads the contents of a file
     * @param <string> $file Path
     * @return <bool> Success
     */
    public function load( $class ){
        if( !class_exists($class) ){
            __autoload( $class ); // ?
        }
        $this->classes[$class] = new ReflectionClass($class);
        return true;
    }

    /**
     *
     * @param <string> $class
     * @return <ReflectionClass>
     */
    public function getClass( $class ){
        if( !isset($this->classes[$class]) ){
            $this->load( $class );
        }
        return $this->classes[$class];
    }

    /**
     * Returns HTML-formatted description of class
     * @param <string> $class
     * @return <string>
     */
    public function html( $class ){
        // create title, description
        $html[] = $this->getHeadHtml( $class );
        // create properties list
        $html[] = '<h3>Properties</h3>';
        $html[] = $this->getPropertiesHtml( $class );
        // create methods list
        $html[] = '<h3>Methods</h3>';
        $html[] = $this->getMethodsHtml( $class );
        // return
        return implode("\n", $html);
    }

    /**
     * Returns HTML-formatted description of class (alias of 'html')
     * @param <string> $class
     * @return <string>
     */
    public function getHtml( $class ){
        return $this->html( $class );
    }

    /**
     *
     * @param <string> $class
     * @return <string>
     */
    public function getHeadHtml( $class ){
        $comment = $this->getCommentText( $this->getClass($class)->getDocComment() );
        return sprintf( $this->head, $this->getClass($class)->getName(), $comment );
    }

    /**
     * Returns HTML-formatted property list
     * @param <string> $class
     * @return <string>
     */
    public function getPropertiesHtml( $class ){
        $html = array();
        foreach( $this->getClass($class)->getProperties() as $property ){
            if( !$property->isPublic() ) continue;
            $title = $property->getName();
            $items = $this->getCommentHtml( $property->getDocComment() );
            $items .= sprintf( $this->definition, 'Access', $this->getPropertyAccess($property) );
            $html[] = sprintf( $this->properties, $title, $items );
        }
        $html = sprintf( $this->list, implode('', $html) );
        // return
        return $html;
    }

    /**
     * Returns property access text
     * @param <ReflectionProperty> $method
     * @return <string>
     */
    public function getPropertyAccess( $property ){
        return sprintf( '%s%s%s%s',
            $property->isPublic() ? 'public' : '',
            $property->isPrivate() ? 'private' : '',
            $property->isProtected() ? 'protected' : '',
            $property->isStatic() ? ' static' : ''
        );
    }

    /**
     * Returns HTML-formatted method list
     * @param <string> $class
     * @return <string>
     */
    public function getMethodsHtml( $class ){
        $html = array();
        foreach( $this->getClass($class)->getMethods() as $method ){
            if( !$method->isPublic() ) continue;
            $title = $method->getName();
            if( strpos($title, '_') === 0 ) continue;
            $items = $this->getCommentHtml( $method->getDocComment() );
            $items .= sprintf( $this->definition, 'Access', $this->getMethodAccess($method) );
            $html[] = sprintf( $this->methods, $title, $items );
        }
        $html = sprintf( $this->list, implode('', $html) );
        // return
        return $html;
    }

    /**
     * Returns method access text
     * @param <ReflectionMethod> $method
     * @return <string>
     */
    public function getMethodAccess( $method ){
        return sprintf( '%s%s%s%s%s%s%s',
            $method->isInternal() ? 'internal' : 'user-defined',
            $method->isAbstract() ? ' abstract' : '',
            $method->isFinal() ? ' final' : '',
            $method->isPublic() ? ' public' : '',
            $method->isPrivate() ? ' private' : '',
            $method->isProtected() ? ' protected' : '',
            $method->isStatic() ? ' static' : ''
        );
    }



    /**
     * Parse JavaDoc into array
     * @param <string> $string
     * @return <array>
     */
    public function getComment( $string ){
        $start = strpos($string, '/**');
        if( $start !== false ){
            $string = substr( $string, $start + 2 );
        }
        $end = strrpos($string, '*/');
        if( $end !== false ){
            $string = substr( $string, 0, $end - 1 );
        }
        // split
        $_lines = preg_split('/\n/', $string);
        // trim
        $lines = array();
        foreach($_lines as  $line){
            $line = trim($line, $this->unwanted);
            if( empty($line) ) continue;
            else $lines[] = $line;
        }
        // return
        return $lines;
    }

    /**
     * Return comment string without comment markings ('*')
     * @param <string> $string
     * @return <string>
     */
    public function getCommentText( $string ){
        $lines = $this->getComment($string);
        return implode(" \n", $lines);
    }

    /**
     * Return HTML-formatted JavaDoc
     * @param <string> $string
     * @return <string>
     */
    public function getCommentHtml( $string ){
        $html = array();
        $lines = $this->getComment($string);
        $description = array();
        foreach( $lines as $line ){
            if( strpos($line, '@') === 0 ){
                list( $term, $definition ) = explode(' ', $line, 2);
            }
            else{
                // add to description
                $description[] = $line;
                continue;
            }
            // format
            $term = substr(strtolower($term), 1);
            $term = $this->map( $term );
            $term = ucfirst($term);
            if( $definition ) $definition = ucfirst(htmlentities($definition));
            else $definition = '&nbsp;';
            // print
            $html[] = sprintf( $this->definition, $term, $definition );
        }
        // print description
        if( $description ){
            $description = implode("<br/>\n", $description);
            $description = sprintf( $this->definition, 'Description', $description );
            array_unshift($html, $description);
        }
        // return
        return implode(" \n", $html);
    }

    /**
     * Replaces '@abc' tokens with map
     * @param <string> $key
     * @return <string>
     */
    public function map( $key ){
        if( isset($this->map[$key]) ){
            $key = $this->map[$key];
        }
        return $key;
    }
}