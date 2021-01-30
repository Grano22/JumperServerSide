<?php
define("TEMPLATE_ALLOWED_OPENED_TAGS", ["exclude", "excluded", "block", "include", "included"]);
define("TEMPLATE_ALLOWED_CLOSED_TAGS", ["exclude", "excluded", "block", "include", "included"]);

function includeChunkedTemplate($path, $delegate=false, $passRouter=null, $closeConn=false) {
    try {
        /*if($delegate) {
            include API_PATH."libraries/JumperServerSide/delegation.php";

        }*/
        if($passRouter) extract(["_ROUTER"=>$passRouter]);
        ob_start();
        include $path;
        $output = ob_get_clean();
        if(strpos($output, "<!--@")==0) $output = substr($output, strpos($output, "\n") + 1);
        echo $output;
    } catch(Exception $e) {
        echo "error";
    }
}

function flushTemplateTags(string $output) {
    $output = preg_replace('/\<(excluded|included)((\s([A-z0-9-]*\=\"[^"]*\"|[A-z0-9-]*))+)?\>([A-z0-9_\-\. ]+)?\<\/(excluded|included)\>/', "", $output);
    return $output;
}

function parseTemplateElements($inpStr, $rtrim=true, $ltrim=true) {
    $seq = str_split($inpStr);
    $allowedOpenTags = ["exclude", "excluded", "block", "include", "included"];
    $allowedClosedTags = [];
    $openedTags = 0; $closedTags = 0;
    $els = [];
    $inTag = 0; //0 - out of tag, 1 - parsing tag name, 2 - parsing attributes, 3 - in attr, 4 - inner tag, 5 - enclosing tag
    $initialTag = [ "tagName"=>"", "namedAttrs"=>[], "indexedAttrs"=>[], "outerHTML"=>"", "children"=>[], "elType"=>1 ];
    $lastTag = $initialTag;
    $str = "";
    $charInd = 0;
    for($charInd = 0;$charInd<count($seq);$charInd++) {
        $outChar = $seq[$charInd];
        if($inTag>0) $lastTag["outerHTML"] .= $seq[$charInd];
        if(!$inTag && $seq[$charInd]=="<" && trim($seq[$charInd + 1])!="") {
            //if($seq[$charInd + 1]=="/") { echo "Parsing error"; return false; }
            if($seq[$charInd + 1]!="/") { 
                $lastTag["outerHTML"] .= $seq[$charInd];
                $inTag = 1;
                $str = "";
            }
        } else if($inTag==1 && trim($seq[$charInd])=="") {
            if(in_array($str, array_merge($allowedOpenTags, $allowedClosedTags))) { $lastTag["tagName"] = $str;
            $str = "";
            $inTag = 2; } else { $inTag = 0; $str = ""; $lastTag = $initialTag; }
        } else if($inTag==2 && $seq[$charInd]=='=' && $seq[$charInd + 1]=='"') {
            $lastAttr = $str;
            $str = "";
            $inTag = 3;
            $lastTag["outerHTML"] .= $seq[$charInd + 1];
            $charInd++;
        } else if($inTag==3 && $seq[$charInd]=='"') { // (trim($outChar)!=$str &&
            $lastTag["namedAttrs"][$lastAttr] = $str;
            $lastAttr = "";
            $str = "";
            $inTag = 2;
            if($seq[$charInd + 1]==">") {
                $containsOpened = in_array($lastTag["tagName"], $allowedOpenTags);
                $containsClosed = in_array($lastTag["tagName"], $allowedClosedTags);
                if(($containsOpened && $containsClosed && $seq[$charInd - 1]!="/") || $containsOpened) {
                    $inTag = 4;
                    $lastTag["elType"] = 2;
                    $str = "";
                    $lastTag["outerHTML"] .= ">";
                    $charInd++;
                } else { $inTag = 0; array_push($els, $lastTag); $lastTag = $initialTag; $str = ""; }
            }
        } else if($inTag==2 && (trim($seq[$charInd])!=$str && ($seq[$charInd + 1]==">" || $seq[$charInd + 1]=="/"))) {
            array_push($lastTag["indexedAttrs"], $str);
            $str = "";
            if($seq[$charInd + 1]==">") {
                $containsOpened = in_array($lastTag["tagName"], $allowedOpenTags);
                $containsClosed = in_array($lastTag["tagName"], $allowedClosedTags);
                if(($containsOpened && $containsClosed && $seq[$charInd - 1]!="/") || $containsOpened) {
                    $inTag = 4;
                    $lastTag["elType"] = 2;
                    $str = "";
                } else { $inTag = 0; array_push($els, $lastTag); $lastTag = $initialTag; $str = ""; }
            }
        } else if($inTag==2 && $seq[$charInd]==">") {
            $containsOpened = in_array($lastTag["tagName"], $allowedOpenTags);
            $containsClosed = in_array($lastTag["tagName"], $allowedClosedTags);
            if(($containsOpened && $containsClosed && $seq[$charInd - 1]!="/") || $containsOpened) {
                $inTag = 4;
                $lastTag["elType"] = 2;
                $str = "";
            } else { $inTag = 0; array_push($els, $lastTag); $lastTag = $initialTag; $str = ""; }
        } else if($inTag==4 && $seq[$charInd]=="<" && $seq[$charInd + 1]=="/" && substr($inpStr, $charInd + 2, strlen($lastTag["tagName"]))==$lastTag["tagName"]) {
            $lastTag["outerHTML"] .= "/{$lastTag["tagName"]}>";
            $lastTag["children"] = parseTemplateElements($str);
            $lastTag["innerHTML"] = ltrim(rtrim($str));
            $inTag = 0;
            array_push($els, $lastTag);
            $lastTag = $initialTag;
        } else if(count($seq) - 1<=$charInd && $seq[$charInd]==">" && $inTag>0) {
            if($inTag==4) {
                $lastTag["children"] = parseTemplateElements($str);
                $lastTag["innerHTML"] = ltrim(rtrim(substr($str, 0, -1 * strlen($lastTag["tagName"]) - 2)));
                array_push($els, $lastTag);
                $lastTag = $initialTag;
            }
        } else if($inTag==4 || trim($seq[$charInd])!="") $str .= $seq[$charInd];
        //$charInd++;
    }
    return $els;
}

function mergeTemplate(array $els, string $ipath, array $templateParams, string $output) {
    $currChilds = $els;
    $ipath = array_slice(explode("/", $ipath), 1, -1);
    $ipath = "/".implode("/", $ipath)."/";
    $queue = [];
    do {
        foreach($currChilds as $elInd=>$el) {
            if($el["tagName"]=="include") {
                if(isset($el["namedAttrs"]["template"])) {
                    if(is_file($ipath.$el["namedAttrs"]["template"])) {
                        $includableOutput = includeTemplate("/$ipath/".$el["namedAttrs"]["template"], $templateParams);
                        if(array_key_exists("children", $el)) {
                            foreach($el["children"] as $childBlock) {
                                if($childBlock["tagName"]=="block") { 
                                    if(array_key_exists("name", $childBlock["namedAttrs"])) {
                                        //$separatedBlocks[$childBlock["namedAttrs"]["name"]] = 
                                        $includableOutput = preg_replace('/\<included(\s?block="'.$childBlock["namedAttrs"]["name"].'")((\s([A-z0-9-]*\=\"[^"]*\"|[A-z0-9-]*))+)?\>([A-z0-9_\-\. ]+)?\<\/included\>/', $childBlock["innerHTML"], $includableOutput);
                                    }
                                }
                            }
                        }
                        //echo htmlentities($output)."<br><hr>";
                        //echo htmlentities($includableOutput)."<br><hr>";
                        //echo htmlentities($el["outerHTML"])."<br><hr>";
                        $output = str_replace($el["outerHTML"], $includableOutput, $output);
                        //echo htmlentities($output);
                    } else echo "Not a file $ipath".$el["namedAttrs"]["template"]."...";
                }
            } else if($el["tagName"]=="exclude") {
                if(isset($el["namedAttrs"]["template"])) {
                    if(is_file($ipath.$el["namedAttrs"]["template"])) {
                        $exclutableOutput = includeTemplate("/$ipath/".$el["namedAttrs"]["template"], $templateParams);  
                        //if(isset($el["namedAttrs"]["block"])) $output = preg_replace('/\<excluded(\s?block="'.$el["namedAttrs"]["block"].'")((\s([A-z0-9-]*\=\"[^"]*\"|[A-z0-9-]*))+)?\>([A-z0-9_\-\. ]+)?\<\/excluded\>/', $el["innerHTML"], $exclutableOutput); else $output = preg_replace('/\<excluded((\s([A-z0-9-]*\=\"[^"]*\"|[A-z0-9-]*))+)?\>([A-z0-9_\-\. ]+)?\<\/excluded\>/', $el["innerHTML"], $exclutableOutput);
                        $output = preg_replace('/\<excluded((\s([A-z0-9-]*\=\"[^"]*\"|[A-z0-9-]*))+)?\>([A-z0-9_\-\. ]+)?\<\/excluded\>/', $el["innerHTML"], $exclutableOutput);
                    } else echo "Not a file $ipath".$el["namedAttrs"]["template"]."...";
                }
            } else if($el["tagName"]=="excluded") {
                /*if(strpos($output, $el["outerHTML"])) {
                    //if(array_key_exist("replaceTo", $childBlock["namedAttrs"])) 
                    $output = preg_replace('/\<excluded((\s([A-z0-9-]*\=\"[^"]*\"|[A-z0-9-]*))+)?\>([A-z0-9_\-\. ]+)?\<\/excluded\>/', "", $output);
                }*/
            }
            if($el["tagName"]!="block" && array_key_exists("children", $el) && is_array($el["children"])) { 
                foreach($el["children"] as $elChild) array_push($queue, $elChild);
            }
        }
        $currChilds = $queue;
        $queue = [];
    } while(is_array($currChilds) && count($currChilds)>0);
    return $output;
}

/**
 * String var declaration to array of keys
 * @param string $tgStr Target string to pass
 */
function strVarnameToKeys(string &$tgStr) : array {
    $outKeys = []; $inKey = "$";
    for($i=0;$i<strlen($tgStr) - 1;$i++) if($tgStr[$i + 1]=="]" && ($tgStr[$i]=="'" || $tgStr[$i]=='"')) { $outKeys[] = $inKey; $inKey = "$"; } else if($inKey!="$") $inKey .= $tgStr[$i]; else if($tgStr[$i - 1]=="[" && ($tgStr[$i]=="'" || $tgStr[$i]=='"')) $inKey = "";
    return $outKeys; 
}

function includeTemplate($filePath, $templateParams, $vars = array(), $print = false, $delegate=false, $stripAfter=false) {
    $output = NULL;
    if(file_exists($filePath)){
        $passedGlobals = ["_TEMPLATE"=>[]];
        if(array_key_exists("__passRouter", $vars)) $passedGlobals["_ROUTER"] = $vars["__passRouter"];
        if($delegate==1 && defined("_DELEGATED")) $templateParams = array_merge_recursive($templateParams, _DELEGATED);
        $vars = array_merge($templateParams, ["vars"=>$vars]);
        $passedGlobals["_TEMPLATE"] = $vars;
        extract($passedGlobals);
        ob_start();
        include $filePath;
        $output = ob_get_clean();
        if(is_array($delegate)) { 
            if(array_key_exists("delegateFrom", $vars)) $output = '<?php include "'.API_PATH.'libraries/JumperServerSide/delegation.php"; includeDelegation('.$vars["delegateFrom"][0].', '.$vars["delegateFrom"][1].', '.(isset($vars["delegateFrom"][2]) ? $vars["delegateFrom"][2] : "json").'); ?>'.PHP_EOL.$output;
            else if(array_key_exists("delegateFromResponse", $vars)) $output = '<?php include "'.API_PATH.'libraries/JumperServerSide/delegation.php"; delegationFromResponse('.$vars["delegateFromResponse"].', '.(isset($vars["delegateFrom"][2]) ? $vars["delegateFrom"][2] : "json").'); ?>'.PHP_EOL.$output;
        }
        $els = parseTemplateElements($output);
        //echo "<xmp>";
        //print_r($els);
        //echo "</xmp>";
        if(count($els)>0) $output = mergeTemplate($els, $filePath, $templateParams, $output);
        //echo htmlentities($output)."<br>";
        foreach($vars as $varNS=>$varVal) { 
            //\$\_TEMPLATE\[(\'|\")([A-z0-9_]+)(\'|\")\]
            //\$\_TEMPLATE(\[(\'|\")(SZULki)(\'|\")\])((\[(\'|\")([A-z0-9_]+)(\'|\")\])+)?
            $matchedVars = null;
            //(count($skip)>0 && !in_array($varNS, $skip)) || 
            if(preg_match_all('/\$\_TEMPLATE(\[(\'|\")('.$varNS.')(\'|\")\])((\[(\'|\")([A-z0-9_]+)(\'|\")\])+)?/', $output, $matchedVars)) {
                foreach($matchedVars[0] as $pattInd=>$patternGroup) {
                    /*$arrKeys = str_replace(["]", '"', "'"], "", $patternGroup);
                    $arrKeys = explode("[", $arrKeys);
                    array_shift($arrKeys);*/
                    $arrKeys = strVarnameToKeys($patternGroup);
                    $output = preg_replace('/\$\_TEMPLATE(\[(\'|\")('.$varNS.')(\'|\")\])((\[(\'|\")([A-z0-9_]+)(\'|\")\])+)?/', (string)arrayRecValue($vars, $arrKeys), $output);
                }
            }
            //$output = preg_replace('/\$\_TEMPLATE\[(\'|\")('.$varNS.')(\'|\")\]/', $varVal, $output);
        }
    } else {
        echo "Template not found in $filePath...";
        exit();
    }
    if($stripAfter) $output = flushTemplateTags($output);
    if($print) echo $output;
    return $output;

}

class JumperRouter {
    private $fullURL = "";

    private $host = "";
    private $hostname = "";
    private $port = 8080;
    
    private $requireSSL = false;

    private $targetScript = "";
    //Parting
    private $params = [];
    private $urlParts = [];
    //Total Patching
    private $rootPath = "/";

    private $inheritedFromRoutes = [];
    private $loadedRoutes = [];
    //Current
    private $_receivedRoute = null;

    //Config
    private $config;

    //Planned tasks
    private $onExitTasks = [];

    //Errors and debuging
    private $errors = [];
    private $warnings = [];

    function __construct($initialRoutes=[], $config) {
        //$_SERVER['REQUEST_URI'] $_SERVER['QUERY_STRING'] $_SERVER['HTTP_REFERER']
        $this->fullURL = (stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $urlDataParts = parse_url($this->fullURL);
        $this->host = $_SERVER['HTTP_HOST'];
        $this->hostname = $urlDataParts["host"]; //$_SERVER['SERVER_NAME']
        
        if(isset($_SERVER["QUERY_STRING"])) { $parsedParams =  explode("&", $_SERVER["QUERY_STRING"]);
        $this->params = strlen($parsedParams[0])>0 ? $parsedParams : []; } else $this->params = [];

        $urlPrep = $_SERVER['REQUEST_URI'];
        if(strpos($urlPrep, "/")==0) $urlPrep = preg_replace('/\//', "", $urlPrep, 1);
        //if(strrpos($urlPrep, "/")==strlen($urlPrep) - 1) $urlPrep = rtrim($urlPrep, "/");
        $this->urlParts = explode("/", $urlPrep);
        
        $this->rootPath = $_SERVER["DOCUMENT_ROOT"];
        $this->port = $_SERVER['SERVER_PORT'];
        $this->targetScript = $_SERVER["SCRIPT_FILENAME"];

        $this->loadedRoutes = $initialRoutes;

        $this->config = array_merge(["templatesPath"=>"./templates", "nonDelegatedType"=>"php", "debug"=>false], $config);
        if(!array_key_exists("chunkedTemplatesPath", $this->config)) $this->config["chunkedTemplatesPath"] = $this->config["templatesPath"]."chunked";
        if(!is_dir($this->config["chunkedTemplatesPath"])) {
            try {
                mkdir($this->config["chunkedTemplatesPath"], 0777, true);
            } catch(Exception $e) {
                var_dump($e);
            }
        }
    }

    /* Errors and Debuging */
    function addError($description, $name="Unknown Error") {

    }

    function __get($name) {
        switch($name) {
            case "isAllowedSSL":
                if(( ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ( ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
                || ( ! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
                || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')) return true; else return false;
            break;
            case "rootPath":
                return $_SERVER["DOCUMENT_ROOT"];
            break;
            case "receivedRoute":
                if($this->_receivedRoute!=null) return $this->_receivedRoute; else {
                    $inherited = $this->inheritFromCurrPath();
                    $currRoute = $this->receiveRoute();
                    if($inherited!=null && (!array_key_exists("noInherit", $currRoute) || $currRoute["noInherit"]!=true)) $inherited = array_merge($inherited, $currRoute); else $inherited = $currRoute;
                    $inherited = array_merge($inherited, $this->inheritToLastPatch());
                    $this->_receivedRoute = $inherited;
                    return $this->_receivedRoute;
                }
            break;
        }
    }

    function createRenderChunk($vars=[], $ext="php", $render=false, $delegate=false, $passRouter=null) {
        if(array_key_exists("template", $this->receivedRoute)) {
            if($passRouter!=null) $vars["__passRouter"] = $passRouter;
            $contentRendered = includeTemplate($this->config["templatesPath"].$this->receivedRoute["template"], $this->receivedRoute, $vars, $render, $delegate, true);
            $chunkedPath = $this->config['chunkedTemplatesPath']."/".str_replace(".php", "", $this->receivedRoute['template'])."_chunked.".($delegate ? "php" : $this->config["nonDelegatedType"]);
            $hashedTemplate = hash_file('md5', $this->config["templatesPath"].$this->receivedRoute["template"]);
            file_put_contents($chunkedPath, "<!--@$hashedTemplate|".date("Y-m-d H:i:s")."-->".PHP_EOL.$contentRendered);
            //$p = new Phar($chunkedPath);
            //$p->setMetadata(["checksum"=>hash_file('md5', $this->config["templatesPath"].$this->receivedRoute["template"])]);
            //file_put_contents($this->config['chunkedTemplatesPath']."/".str_replace(".php", "", $this->receivedRoute['template'])."_chunked.lock", $hashedTemplate);
        }
        
    }

    function renderAsNotChunked($vars=[], $delegate=false) {
        //\$\_TEMPLATE(\[(\'|\")([A-z0-9_]+)?.+(\'|\")\])
        //\$\_TEMPLATE(\[(\'|\")([A-z0-9_]+)(\'|\")\])+
        //\$\_TEMPLATE(\[(\'|\")(SZULki)(\'|\")\])(\[(\'|\")([A-z0-9_]+)(\'|\")\])+
        if(array_key_exists("template", $this->receivedRoute)) { 
            if($delegate) {
                include "delegation.php";
                if(array_key_exists("delegateFrom", $this->receivedRoute)) {
                    includeDelegation($this->receivedRoute["delegateFrom"][0], $this->receivedRoute["delegateFrom"][1], isset($this->receivedRoute["delegateFrom"][2]) ? $this->receivedRoute["delegateFrom"][2] : "json");
                } else if(array_key_exists("delegateFromResponse", $this->receivedRoute)) {
                    delegationFromResponse($this->receivedRoute["delegateFromResponse"]);
                }
            }
            includeTemplate($this->config["templatesPath"].$this->receivedRoute["template"], $this->receivedRoute, $vars, true, $delegate);
        }
    }

    function checkRouteConfigDiff() {
        //$currRouteCheck = hash("md5", serialize($this->loadedRoutes));
        //$currRouteCheck = spl_object_hash($this->loadedRoutes);
        $currRouteCheck = hash("md5", json_encode($this->loadedRoutes));
        if(!is_file($this->config["templatesPath"]."routes.lock")) { file_put_contents($this->config["templatesPath"]."routes.lock", $currRouteCheck); return false; }
        $lastRouteCheck = file_get_contents($this->config["templatesPath"]."routes.lock");
        //var_dump($lastRouteCheck, $currRouteCheck);
        if($currRouteCheck==$lastRouteCheck) return true; else file_put_contents($this->config["templatesPath"]."routes.lock", $currRouteCheck);
        return false;
    }

    function render($vars=[]) {
        $delegate = false;
        $templateHash = null;
        $chunkedHash = null;
        if(array_key_exists("delegateFrom", $this->receivedRoute) || array_key_exists("delegateFromResponse", $this->receivedRoute)) if(array_key_exists("delegate", $this->receivedRoute)) $delegate = $this->receivedRoute['delegate']; else $delegate = 1;
        $templatePath = $this->config["templatesPath"].$this->receivedRoute["template"];
        $chunkedPath = $this->config['chunkedTemplatesPath']."/".str_replace(".php", "", $this->receivedRoute['template'])."_chunked.".($delegate ? "php" : $this->config["nonDelegatedType"]);
        $templateChunkDataPath = $this->config['chunkedTemplatesPath']."/".str_replace(".php", "", $this->receivedRoute['template'])."_chunked.lock";
        if(is_file($templatePath)) { $templateHash = hash_file("md5", $templatePath);
        if(is_file($chunkedPath)) {
            $chunkedHash = str_replace(["<!--@", "-->"], "", fgets(fopen($chunkedPath, 'r')));
            $chunkedHash = substr($chunkedHash, 0, strpos($chunkedHash, "|"));
        }
        if((!array_key_exists("reloadAlways", $this->config) || !$this->config["reloadAlways"]) && $this->checkRouteConfigDiff() && $chunkedHash!=null && $templateHash==$chunkedHash) includeChunkedTemplate($chunkedPath, false); else $this->createRenderChunk($vars, "php", true, array_key_exists("delegate", $this->receivedRoute) ? $this->receivedRoute : boolval($delegate));
        } else if(array_key_exists("debug", $this->config) && $this->config["debug"]) echo "Template not found!";
    }

    function closeConnection() {
        ob_end_flush();
        flush();
    }

    function delegateTask($func) {

    }

    function receiveRoute() {
        $mergedPath = implode("/", $this->urlParts);
        if(array_key_exists($mergedPath, $this->loadedRoutes)) return $this->loadedRoutes[$mergedPath];
        else return $this->loadedRoutes[404];
    }

    function parseRoutes() {

    }

    /*function inheritRoutes() {
        $parts = explode("/", $this->fullURL);
        $targetParts = ["*"];
        $depth = 0;
        $potential = [];
        $mergedConfig = [];
        foreach($this->loadedRoutes as $routeID=>$routeParams) {
            $routeID = rtrim($routeID);
            if(substr($routeID, -1)=="*") $potential[$routeID] = $routeParams;
        }
        $iter = 0;
        while($depth<=cout($parts)) {
            $currPotentialPath = implode("/", $targetParts);
            if(array_key_exists($currPotentialPath, $potential)) $mergedConfig = array_merge($mergedConfig, $potential[$currPotentialPath]);
            $targetParts = array_slice($parts, $depth + 1, 0);
            $depth++;
        }
    }*/

    function inheritFromCurrPath() {
        $parts = $this->urlParts;
        $parts[count($parts) - 1] = "*";
        $parts = implode("/", $parts);
        if(array_key_exists($parts, $this->loadedRoutes)) return $this->loadedRoutes[$parts]; else return null;
    }

    function inheritToLastPatch() {
        $parts = $this->urlParts;
        $inherited = [];
        $parts[count($parts) - 1] = "$";
        $parts = implode("/", $parts);
        foreach($this->loadedRoutes as $routePath=>$routeParams) {
            if(substr($routePath, -1)=="$") {
                $routeSerp = substr($routePath, 0, -1);
                //var_dump($parts, $routeSerp, $routePath);
                if($routeSerp=="" || strpos($parts, $routeSerp)!==false) $inherited = array_merge($inherited, $routeParams);
            }
        }
        return $inherited;
    }

    //Static
    public static function urljoin() { return implode("/", func_get_args ()); }
}

class JumperRouteTemplate {
    function __construct() {

    }
}
?>