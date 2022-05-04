<?php

use HardJunior\Route\Router;


$router = new Router(site());
$router->namespace("Source\Controllers");
/**
 * 
 * Web
 */
$router->group(null);

$router->get("/", "Web:login", "web.login");
$router->get("/cadastrar", "Web:register", "web.register");
$router->get("/recuperar", "Web:forget", "web.forget");
$router->get("/senha/{email}/{forget}", "Web:reset", "web.reset");

/**
 * AUTH
 */
$router->group(null);
$router->post("/login", "Auth:Login", "auth.login");
$router->post("/register", "Auth:register", "auth.register");
$router->post("/forget", "Auth:forget", "auth.forget");
$router->post("/reset", "Auth:reset", "auth.reset");

/**
 * AUTH SOCIAL
 */


/**
 * PROFILE
 */
$router->group("/me");
$router->get("/", "App:home", "app.home");
$router->get("/sair", "App:logoff", "app.logoff");


/**
 * ERRORS
 */
$router->group("ops");
$router->get("/{errcode}", "Web:error", "web.error");

/**
 * ROUTE PROCESS
 */



/**
 * This method executes the routes
 */
$router->dispatch();

/*
 * Redirect all errors
 */
if ($router->error()) {
    $router->redirect("web.error", ["errcode" => $router->error()]);
}
