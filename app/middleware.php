<?php /** @noinspection PhpUnused */
declare(strict_types=1);

use app\handlers\HttpErrorHandler;
use app\handlers\ShutdownHandler;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;


return function (App $app) {


    $settings = $app->getContainer()->get('settings');
    $error_settings = $settings->error_settings;
    //$app->addErrorMiddleware($error_settings->display_error_details, $error_settings->log_error_details, $error_settings->log_errors);


    $callableResolver = $app->getCallableResolver();
    $responseFactory = $app->getResponseFactory();

    $serverRequestCreator = ServerRequestCreatorFactory::create();
    $request = $serverRequestCreator->createServerRequestFromGlobals();

    $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory,
        $app->getContainer()->get(LoggerInterface::class),$app->getContainer());
    $shutdownHandler = new ShutdownHandler($request, $errorHandler, $error_settings->display_error_details);
    register_shutdown_function($shutdownHandler);

// Add Routing Middleware
    $app->addRoutingMiddleware();

// Add Error Handling Middleware
    $errorMiddleware = $app->addErrorMiddleware($error_settings->display_error_details, $error_settings->log_error_details, $error_settings->log_errors);
    $errorMiddleware->setDefaultErrorHandler($errorHandler);
};