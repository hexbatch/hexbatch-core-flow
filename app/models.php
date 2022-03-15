<?php /** @noinspection PhpUnused */

declare(strict_types=1);

use app\models\base\FlowBase;
use DI\Container;


return function (Container $container) {
    FlowBase::set_container($container);
};
