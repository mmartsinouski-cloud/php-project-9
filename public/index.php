<?php

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create(new ResponseFactory());

$renderer = new PhpRenderer(__DIR__ . '/../templates');
$renderer->setLayout('layout/app.phtml');

$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) use ($renderer) {
    return $renderer->render($response, 'index.phtml');
});

$app->run();
