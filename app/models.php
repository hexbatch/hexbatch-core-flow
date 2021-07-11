<?php

declare(strict_types=1);

use app\models\project\FlowProject;
use app\models\project\FlowProjectUser;
use app\models\user\FlowUser;
use DI\Container;


return function (Container $container) {
    FlowUser::set_container($container);
    FlowProject::set_container($container);
    FlowProjectUser::set_container($container);
};
