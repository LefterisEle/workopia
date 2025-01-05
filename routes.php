<?php

$router->get('/', 'controllers/home.php');
$router->get('/listings', 'controllers/listings/index.php');
$router->get('/listings/create', 'controllers/create.php');
$router->get('404', 'controllers/error/404.php');
