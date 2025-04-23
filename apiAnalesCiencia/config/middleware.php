<?php

use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;
use Slim\App;
use Slim\Handlers\ErrorHandler;
use TDW\ACiencia\Handler\{ HtmlErrorRenderer, JsonErrorRenderer };
use TDW\ACiencia\Middleware\CorsMiddleware;

return function (App $app) {
    /** @var ContainerInterface $container */
    $container = $app->getContainer();

    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    $app->add(CorsMiddleware::class);

    $app->addRoutingMiddleware();
    // $app->add(BasePathMiddleware::class);

    // Error handler
    $settings = $container->get(Configuration::class)->getArray('error_handler_middleware');
    $displayErrorDetails = (bool) $settings['display_error_details'];
    $logErrors = (bool) $settings['log_errors'];
    $logErrorDetails = (bool) $settings['log_error_details'];

    $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

    /** @var ErrorHandler $errorHandler */
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
    $errorHandler->registerErrorRenderer('application/json', JsonErrorRenderer::class);
};
