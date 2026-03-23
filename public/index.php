<?php

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create(new ResponseFactory());

$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write("Welcome to Slim Framework!");
    return $response;
});

$app->run();
