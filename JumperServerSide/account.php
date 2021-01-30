<?php
include "networkUtils.php";

class JumperSession {
    private $config = [
        "sessionIDCookieName" => "JSSID",
        "sessionLastClientNavName" => "JSCLN",
        "sessionExpirationTime"=> 7200,
        "sessionFilePath" => "api/backend/",
        "sessionFileName" => "JSSID",
        "allowSubdomain" => false,
        "sendSessionToCookies" => true,
        "useSessionFile" => true
    ];
    private $sessionsData = [];

    function __construct() {
        $this->config["sessionFilePath"] = $_SERVER["DOCUMENT_ROOT"]."/".$this->config["sessionFilePath"];
        $this->start();
    }

    function __get($name) {
        switch($name) {
            case "sessionFile":
                return $this->config["sessionFilePath"].$this->config["sessionFileName"].".php";
        }
    }

    function start() {
        if(!array_key_exists($this->config["sessionIDCookieName"], $_COOKIE)) {
            $this->prepareSessionData();
        } else {
            $this->sessionsData = $this->getFromFile($_COOKIE[$this->config["sessionIDCookieName"]]);
            if($this->sessionsData==null) {
                $this->sessionsData = [];
            }
            //$this->sessionsData["id"] = $_COOKIE[$this->config["sessionIDCookieName"]];
            if(array_diff_key(array_flip(["id", "clientIP", "geoLoc", "agent", "creationT", "urlLastAccessT", "inAppLastAccessN"]), $this->sessionsData)) $this->prepareSessionData();
        }
        if($this->config["useSessionFile"]) { if(!file_exists($this->sessionFile)) $this->createFile();
        if($this->isExistInFile()===false) $this->saveToFile(); }
    }

    function regenerate() {

    }

    function destroy() {

    }

    //Storing
    function preparePairs() {
        $res = "";
        foreach($this->sessionsData as $sessKey=>$sesVal) {
            $res .= "$sessKey=$sesVal".PHP_EOL;
        }
        return $res;
    }

    function createFile() {
        $cfc = <<<_FC
<?php /**<!--JSC$
{$this->preparePairs()}
\$JSC-->**/ ?>
_FC;
        file_put_contents($this->sessionFile, $cfc);
    }

    function saveToFile($customData=null) {
        if($customData==null) $customData = $this->sessionsData;
        $prepCont = file_get_contents($this->sessionFile);
        file_put_contents($this->sessionFile, rtrim(substr($prepCont, 0, strrpos($prepCont, "\$JSC"))).PHP_EOL.$this->preparePairs().substr($prepCont, strrpos($prepCont, "\$JSC")));
    }
    //$prepCont = substr($prepCont, strpos($prepCont, "JSC$") + 4, strrpos($prepCont, "JSC$") - 4);

    function getAllFromFile() {
        $outputs = [];
        $startParsing = "";
        $handle = fopen($this->sessionFile, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = ltrim(str_replace(["\n", "\r"],"", $line));
                $posr = strpos($line, "id=");
                if($posr==0) {
                    $startParsing = substr($line, $posr + strlen($posr));
                    $outputs[$startParsing] = [];
                } else if($startParsing && trim($line)!="") {
                    $equalation = strpos("=");
                    $outputs[$startParsing][substr($line, 0, $equalation)] = substr($line, $equalation + 1);
                }
            }
        
            fclose($handle);
            return $outputs;
        } else {
            return null;   
        }
    }

    function isExistInFile($sessID="") {
        if($sessID=="") $sessID = isset($this->sessionsData["id"]) ? $this->sessionsData["id"] : "";
        $handle = fopen($this->sessionFile, "r");
        if($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = trim(str_replace(["\n", "\r"],"", $line));
                if($line=="id=".$sessID) return true;
            }
            return false;
        }
    }

    function getFromFile($sessID="") {
        if($sessID=="") $sessID = $this->sessionsData["id"] || "";
        $outputs = [];
        $handle = fopen($this->sessionFile, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = ltrim(str_replace(["\n", "\r"],"", $line));
                if(!array_key_exists("id", $outputs)) {
                    $posr = strpos($line, "id=");
                    if($posr===0) {
                        $posr = trim(substr($line, $posr + 3));
                        if($posr==$sessID) $outputs["id"] = $posr;
                    }
                } else {
                    if(strpos($line, "id=")===0 || strpos($line, "=")===false) return $outputs;
                    $equalation = strpos($line, "=");
                    $outputs[substr($line, 0, $equalation)] = substr($line, $equalation + 1);
                }
            }
            fclose($handle);
            return $outputs;
        } else {
            return null;
        }
    }

    function syncToDB() {

    }

    //Prepare Session Data
    function prepareSessionData() {
        //GEOIP_COUNTRY_CODE
        $clientIP = getClientIP();
        $creatTime = time();
        $sessID = $this->generateID("sha256", $clientIP, $creatTime);
        $this->sessionsData = [
            "id" => $sessID,
            "clientIP" => $clientIP,
            "geoLoc" => null,
            "agent" => $_SERVER['HTTP_USER_AGENT'],
            "creationT" => $creatTime,
            "urlLastAccessT" => $creatTime,
            "inAppLastAccessN" => ""
        ];
        $serverDomain = $_SERVER['HTTP_HOST'];
        if($serverDomain) $serverDomain = $_SERVER['SERVER_ADDR'].(!in_array(intval($_SERVER["SERVER_PORT"]), [443, 80, 8080]) ? ":".$_SERVER["SERVER_PORT"] : "");
        setcookie($this->config["sessionIDCookieName"], $sessID, $creatTime+$this->config["sessionExpirationTime"], "/", ($this->config["allowSubdomain"] ? "." : "").$serverDomain, isset($_SERVER['HTTPS']), isset($_SERVER['HTTPS']));
        setcookie($this->config["sessionLastClientNavName"], $creatTime);
    }

    //Session IDs Generation
    function generateID(string $algo="sha256", string $customIP="", float $creationT=-1) {
        if($customIP=="") $customIP = getClientIP();
        if($creationT<0) $creationT = time();
        return hash($algo, $customIP.$_SERVER['HTTP_USER_AGENT'].$creationT);
    }

    function __destruct() {

    }
}


?>