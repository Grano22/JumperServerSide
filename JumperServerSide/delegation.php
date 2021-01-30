<?php
function includeDelegation($filePath, $delegationParams, $outputType="json", $saveAsConst=true) {
    $output = null;
    if(file_exists($filePath)) {
        extract(["_DELEGATION"=>$delegationParams]);
        ob_start();
        include $filePath;
        $output = ob_get_clean();
    }
    if($output==null) return [];
    switch($outputType) {
        case "json":
            $output = json_decode($output);
        break;
        case "raw":
        default:
    }
    if($saveAsConst) define("_DELEGATED", $output);
    return $output;
}

function delegationFromResponse($reqParams, $outputType="json", $saveAsConst=true) {
    $output = null;
    $http = new JumperHTTPRequest();
    if(strtolower()=="post") {
        $output = $http->postProcedural($reqParams[1], $reqParams[2]);
    } else if(strtolower($reqParams[0])=="get") {
        $output = $http->getProcedural($reqParams[1]);
    }
    switch($outputType) {
        case "json":
            $output = json_decode($output);
        break;
        case "raw":
        default:
    }
    if($saveAsConst) define("_DELEGATED", $output);
    return $output;
}

function delegationChanger() {

}
?>