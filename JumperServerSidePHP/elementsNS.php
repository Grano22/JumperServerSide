<?php
abstract class JumperFrontendElement {
    //Indetifical
    public $tagName = "undefined";

    //Parameters
    protected $_attrs = [];

    function __get($name) {
        switch($name) {
            case "outerHTML":
                return $this->asHTML();
            break;
            case "id":

            break;
            case "className":
                
            break;
            case "classList":

            break;
        }
    }

    function setAttribute(string $name, string $value) {
        $this->_attrs[$name] = $value;
    }

    function getAttribute(string $name) {
        return $_attrs[$name];
    }

    function removeAttribute(string $name) {

    }

    function __construct($tagName, $attrs=[]) {
        $this->tagName = $tagName;
        foreach($attrs as $attrName=>$attrVal) $this->setAttribute($attrName, $attrVal);
    }

    function asHTML() {return "";}
    function toString() {return $this->asHTML();}
    function __toString() {return $this->toString();}
}

class JumperFrontendClosedElement extends JumperFrontendElement {
    //Contents
    public $innerHTML = "";

    function __construct($tagName, $attrs=[]) {
        parent::__construct($tagName, $attrs);
    }

    function __get($name) {
        switch($name) {
            case "textContent":
                return htmlentities($this->innerHTML);
            break;
        }
    }

    function __set($name, $val) {
        switch($name) {
            case "textContent":
                $this->innerHTML = htmlentities($val);
            break;
        }
    }

    function asHTML() {
        $htmlStr = "<{$this->tagName}";
        foreach($this->_attrs as $attrName=>$attrValue) {
            $htmlStr .= " $attrName=\"$attrValue\"";
        }
        $htmlStr .= ">".$this->innerHTML;
        return $htmlStr = "</{$this->tagName}>";
    }
}

class JumperFrotendSemiclosedElement extends JumperFrontendElement {
    //Contents

    function __construct($tagName, $attrs=[]) {
        parent::__construct($tagName, $attrs);
    }

    function __get($name) {
        switch($name) {

        }
    }

    function asHTML() {
        $htmlStr = "<{$this->tagName}";
        foreach($this->_attrs as $attrName=>$attrValue) {
            $htmlStr .= " $attrName=\"$attrValue\"";
        }
        $htmlStr .= ">";
        return $htmlStr = "</{$this->tagName}>";
    }
}

class JumperFontendScript extends JumperFrontendClosedElement {
    public $src = "";
    public $type = "text/javascript";

    //Values to params


    //Params
    /*public $isRequireClassOfType = false;
    public $isRequireID = true;*/

    function __construct($src, $type="text/javascript", $options) {
        parent::__construct("script");
        $this->src = $src;
        $this->type = $type;
    }
}

class JumperFrontendElementsCollection {
    private $_elements = [];

    function toString() {
        $elementsLines = "";
        foreach($this->_elements as $elementInd=>$elementVal) {
            if(is_subclass_of($elementVal, JumperFrontendElement)) {
                $elementsLines .= $elementVal->toString();
            }
            if(count($this->_elements) - 1>$elementInd) $elementsLines .= PHP_EOL;
        }
        return $elementsLines;
    }

    function __toString() { return $this->toString(); }
}

class JumperFrontendScriptsCollection {
    public $name = "";
    private $_scripts = [];
    
    static $collCount = 0;
    private static function getUniqueID() { self::$collCount++; return self::$collCount; }

    function toString() {
        $scriptsLines = "";
        if(!is_array($this->_scripts)) if(is_string($this->__scripts)) $this->_scripts = [$this->__scripts]; else return "";
        foreach($this->_scripts as $scriptInd=>$scriptVal) {
            if(is_array($scriptVal)) {
                $scriptVal = array_merge(["src"=>"./scriptName.js", "type"=>"text/javascript"], $scriptVal);
                $scriptEl = new JumperFrontendScript($scriptVal["src"], $scriptVal["type"]);
                if(isset($scriptVal["isRequireID"]) && $scriptVal["isRequireID"]) $scriptEl->id = "jsPrerenderedScript_{$this->name}_$scriptInd";
                //$isRequireClassOfType = false;
                $scriptsLines .= $scriptEl->outerHTML;
            } else if($scriptVal instanceof JumperFontendScript) $scriptsLines .= $scriptVal->outerHTML;
            else if(is_string($scriptVal)) $scriptsLines .= '<script id="jsPrerenderedScript_'.$this->name.'_'.$scriptInd.'" type="text/javascript" src="'.$scriptVal.'"></script>';
            //else $this->addError("Unknown typeo of collection data <Frontend.Script>");
            if(count($this->_scripts)-1>$scriptInd) $scriptsLines .= PHP_EOL;
        }
        return $scriptsLines;
    }

    function __toString() {
        return $this->toString();
    }

    function __construct($scriptsArr, $customName=null) {
        $this->_scripts = $scriptsArr;
        //if($customName==null) $this->name = hash("md4", date("ym")); else $this->name = $customName;
        if($customName==null) $this->name = self::getUniqueID(); else $this->name = $customName;
        //uniqid()
    }

    function asArray() {
        return $this->_scripts;
    }
}

class JumperMetaTag extends JumperFrotendSemiclosedElement {
    function __construct($name = "", $content = "") {
        parent::__construct("meta", [ "name"=>$name, "content"=>$content ]);
    }


}
?>