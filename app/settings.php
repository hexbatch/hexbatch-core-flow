<?php /** @noinspection PhpUnused */
declare(strict_types=1);

use DI\Container;
use Symfony\Component\Yaml\Yaml;

return function(Container $container) {
    $container->set('settings', function () {
        $ret = (object)[];
        $what = Yaml::parseFile(HEXLET_BASE_PATH . '/config/settings.yaml',Yaml::PARSE_OBJECT_FOR_MAP);
        foreach ($what as $what_key => $what_thing) {
            $ret->$what_key = $what_thing;
        }

        $what = Yaml::parseFile(HEXLET_BASE_PATH . '/config/database.yaml',Yaml::PARSE_OBJECT_FOR_MAP);
        foreach ($what as $what_key => $what_thing) {
            $ret->$what_key = $what_thing;
        }

        return $ret;
    });
};