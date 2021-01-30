<?php
/* Jumper - Server Side PHP Library */
function arrayRecValue($targetArray, $arrOfNames, $depth = 0) {
    if($depth>=count($arrOfNames) - 1) return $targetArray[$arrOfNames[$depth]]; else return arrayRecValue($targetArray[$arrOfNames[$depth]], $arrOfNames, $depth++);
}
abstract class JumperLog {
    public $name = "Unknown Error";
    public $description = "";

    private $creationDate = "0000-00-00 00:00:00";

    function __construct(string $descrption, string $name="Unknown Error") {
        $this->description = $description;
        $this->name = $name;
        $this->creationDate = date("Y-m-d H:i:s");
    }
}

class JumperError extends JumperLog {
    protected $num = -1;
    protected $priority = 0;

    function __construct(string $descrption, string $name="Unknown Error", int $errNum=-1, int $priority = 0) {
        parent::__construct($description, $name);
        $this->num = $errNum;
        $this->priority = $priority;
    }
}
class PromiseAlike {
    function __construct() {

    }

    function then() {

    }

    function reject() {

    }
}

class JumperHTTPRequest {
    public $timeout = 4000;

    function __construct(array $metaData=[]) {

    }

    function get() {

    }

    function post() {

    }

    function getProcedural(string $url) {
        return file_get_contents($url, stream_context_create([
            'http' => [
                'timeout' => $this->timeout
            ]
        ]));
    }

    function postProcedural(string $url, array $data) {
        return file_get_contents($url, stream_context_create([
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => $this->timeout
            ]
        ]));
    }

    function getNative() {

    }

    function postNative() {

    }

    function getNativeAwaited() {

    }

    function postNativeAwaited() {

    }
}
?>