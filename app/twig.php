<?php

declare(strict_types=1);

use app\helpers\ProjectHelper;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use Ramsey\Uuid\Uuid;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extension\AbstractExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Loader\FilesystemLoader;
use DI\Container;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Hexlet_Twig_Extension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return [
            'root_url' => $root,
            'csrf_token_set_to_root' => FlowAntiCSRF::SET_LOCK_TO_ANY_PAGE
        ];
    }

    public function getFilters() : array
    {
        $better_json = new TwigFilter('to_json_string', 'app\\hexlet\\JsonHelper::toString',['is_safe' => ['html']]);
        return [$better_json];
    }

    public function getFunctions(): array
    {
        $csrf = new TwigFunction(
            'form_token',
            function($lock_to = null) {

                if ($lock_to === FlowAntiCSRF::SET_LOCK_TO_ANY_PAGE) {
                    $lock_to = '';
                    $csrf = new FlowAntiCSRF($_POST,$_SESSION,FlowAntiCSRF::$fake_server);
                } else {
                    $csrf = new FlowAntiCSRF;
                }
                return $csrf->insertToken($lock_to, false);
            },
            ['is_safe' => ['html']]
        );

        $session_dump = new TwigFunction(
            'dump_session',
            function() {
                ob_start();
                print "<h6>Session Dump</h6><pre class='bg-info text-dark p-1' style='font-size: 0.65em;margin-bottom: 10em; width: fit-content'>";
                var_dump($_SESSION);
                print "</pre>";
                $output = ob_get_contents();
                ob_end_clean();
                return $output;
            },
            ['is_safe' => ['html']]
        );

        $generate_uuid = new TwigFunction(
            'uuid',
            function() {
                $uuid = Uuid::uuid4();
                return $uuid->toString();
            },
            ['is_safe' => ['html']]
        );

        return [$csrf,$session_dump,$generate_uuid];
    }

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
        $loader = new FilesystemLoader([HEXLET_TWIG_TEMPLATE_PATH, HEXLET_TWIG_PAGES_PATH],
            HEXLET_TWIG_TEMPLATE_PATH);

        $settings_as_array = JsonHelper::fromString(JsonHelper::toString($settings),true,true);
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
