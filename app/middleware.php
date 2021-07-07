<?php
declare(strict_types=1);
use Slim\App;


return function (App $app) {


    $settings = $app->getContainer()->get('settings');
    $error_settings = $settings->error_settings;
    $app->addErrorMiddleware($error_settings->display_error_details, $error_settings->log_error_details, $error_settings->log_errors);

};