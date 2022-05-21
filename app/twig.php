<?php

declare(strict_types=1);

use app\handlers\TwigProjectSetter;
use app\helpers\ProjectHelper;
use app\helpers\StandardHelper;
use app\helpers\Utilities;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\models\standard\FlowTagStandardAttribute;
use Ramsey\Uuid\Uuid;
use Slim\App;
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

        $standard_helper = StandardHelper::get_standard_helper();
        $viewable_standard_names = $standard_helper->get_viewable_standards();

        $viewable_standard_scripts = [];
        foreach ($viewable_standard_names as $standard_name) {
            $viewable_standard_scripts[$standard_name] = $standard_helper->get_viewable_template_for_standard($standard_name);
        }

        $viewable_standard_frames = [];
        foreach ($viewable_standard_names as $standard_name) {
            $maybe_frame =  $standard_helper->get_viewable_template_for_standard($standard_name,true);
            if ($maybe_frame) {$viewable_standard_frames[$standard_name] = $maybe_frame;}
        }

        $editable_standard_names = StandardHelper::get_standard_helper()->get_editable_standards();

        $editable_standard_scripts = [];
        foreach ($editable_standard_names as $standard_name) {
            $editable_standard_scripts[$standard_name] = $standard_helper->get_editable_template_for_standard($standard_name);
        }

        $editable_standard_frames = [];
        foreach ($viewable_standard_names as $standard_name) {
            $maybe_frame =  $standard_helper->get_editable_template_for_standard($standard_name,true);
            if ($maybe_frame) {$editable_standard_frames[$standard_name] = $maybe_frame;}
        }

        return [
            'root_url' => $root,
            'csrf_token_set_to_root' => FlowAntiCSRF::SET_LOCK_TO_ANY_PAGE,
            'program_version' => Utilities::get_utilities()->get_version_string(),
            'program_version_description' => Utilities::get_utilities()->get_version_description(),
            'program_version_link' => Utilities::get_utilities()->get_version_link(),
            'supported_git_hosts' => ProjectHelper::get_project_helper()->get_allowed_git_sites(),
            'standard_viewable_scripts' => $viewable_standard_scripts,
            'standard_editable_scripts' => $editable_standard_scripts,
            'standard_viewable_frames' => $viewable_standard_frames,
            'standard_editable_frames' => $editable_standard_frames,
            'font_names' => Utilities::get_utilities()->get_fonts()
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

        $print_nice = new TwigFunction(
            'print_nice',
            function(mixed $what) {
                return Utilities::print_nice($what);
            },
            ['is_safe' => ['html']]
        );


        $standard_keys = new TwigFunction(
            'standard_keys',
            function(string $key_name) : array {
                return FlowTagStandardAttribute::getStandardAttributeKeys($key_name);
            },
            []
        );

        return [$csrf,$session_dump,$generate_uuid,$standard_keys,$print_nice];
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
        $twig =  new TwigProjectSetter($loader, $settings_as_array);
        $twig->addExtension(new Hexlet_Twig_Extension());

        $twig->addExtension(new DebugExtension());



        //$env->addGlobal('root_url', );
        return $twig;
    });

    $container->set('twigMiddleware', function() use ($app, $container) {
        return new TwigMiddleware($container->get('view'), $app->getRouteCollector()->getRouteParser());
    });

};
