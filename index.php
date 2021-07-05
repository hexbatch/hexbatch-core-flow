<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/phpinfo', function (Request $request, Response $response, $args) {

    ob_start();
    phpinfo();
    $info = ob_get_clean();
    $response->getBody()->write($info);
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response) {
    $routeContext = RouteContext::fromRequest($request);
    $routingResults = $routeContext->getRoutingResults();

    // A route's allowed methods are available at all times now and not only when an error arises like in Slim 3
    $allowedMethods = implode(',',$routingResults->getAllowedMethods());

    // Get all of the route's parsed arguments e.g. ['name' => 'John']
    $routeArguments = $routingResults->getRouteArguments();
    $response->getBody()->write("Hello {$routeArguments['name']} ".$allowedMethods);



    return $response;
});

$app->run();
