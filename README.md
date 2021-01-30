# JumperServerSide
Jumper Server Side is a server-side crossplatform framework for server-side rendering.

## Features:
* delegation - server-side script can be delegated for example including RESTfull file response or call it using socket
* templates - create a templates file to prepare your server-side rendering with routing. Template files will be chunked into minimalist php or html static (if specifed) to faster execution in browser
* router - like MVC you can specify routings to prepare your website tree
* session - custom sessions interface (DB mode, cookies mode, mixed mode) opposite to offical session_start()
* tools - some tools example of network to prepare data
* more in future...

Example usage of Routing in PHP
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
