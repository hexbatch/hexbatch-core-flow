<?php

declare(strict_types=1);

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extension\AbstractExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Loader\FilesystemLoader;
use DI\Container;

class Hexlet_Twig_Extension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        $root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ;
        return [
            'root_url' => $root,
        ];
    }

    // ...
}

return function (App $app) {
    /**
     * @var Container $container
     */
    $container = $app->getContainer();

    $container->set('view', function() use ($container) {
        $settings = $container->get('settings')->twig;
        if ($settings->cache) {
            $settings->cache = HEXLET_TWIG_CATCHE_PATH;
        }
        $loader = new FilesystemLoader([HEXLET_TWIG_TEMPLATE_PATH, HEXLET_TWIG_PAGES_PATH, HEXLET_TWIG_PARTIALS_PATH],
            HEXLET_TWIG_TEMPLATE_PATH);

        $settings_as_array = json_decode(json_encode($settings),true);
        $twig =  new Twig($loader, $settings_as_array);
        $twig->addExtension(new Hexlet_Twig_Extension());

        $twig->addExtension(new DebugExtension());

        //$env->addGlobal('root_url', );
        return $twig;
    });

    $container->set('twigMiddleware', function() use ($app, $container) {
        return new TwigMiddleware($container->get('view'), $app->getRouteCollector()->getRouteParser());
    });

};
