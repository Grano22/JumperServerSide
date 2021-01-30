# JumperServerSide for PHP 7.2 and upper

Example usage of Routing
```php
define("BACKEND_DIR", $_SERVER["DOCUMENT_ROOT"]."/backend/");
define("JUMPER_PATH", BACKEND_DIR."libraries/JumperServerSide/");
include JUMPER_PATH."account.php";
$sess = new JumperSession();
include JUMPER_PATH."init.php";

define("FRONTEND_JS_PATH", "/app/assets/bundle/");
define("FRONTEND_JS_POSTFIX", ".bundle");

//$ - means route from subpath to end, * - means route part in 
$routes = [
    "$"=>[
        "lang" => "en-US",
        "PUBLIC_URL" => "/"
    ],
    "*"=>[
        "template" => "myAwesomeTemplate.php",
        "reactAppScript" => new JumperFrontendScriptsCollection([
            FRONTEND_JS_PATH."myAwesomeReactApp".FRONTEND_JS_POSTFIX.".js"
        ])
    ],
    "subpath/*" => [ "template" => "myAwesomeSubpathTemplate.php", "title" => "The default title for all in this subpath" ],
    "anotherSubpath/$" => [ "template" => "myAwesomeAnotherTemplate.php", "title" => "Default title in all subpaths after 'anotherSubpath'" ]
];

$router = new JumperRouter($routes, ["templatesPath"=>API_PATH."/templates/", "debug"=>true]); //, "reloadAlways"=>true - this option always will render templates without using chunked
/*echo "<pre>";
print_r($router);
echo "</pre>";*/
$currentRoute = $router->receivedRoute; //get received route (from current path)
$router->render(); //Render page from tempate and routes configuration
```
