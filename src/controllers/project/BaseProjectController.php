<?php
namespace app\controllers\project;

use app\controllers\base\BasePages;
use app\helpers\ProjectHelper;

class BaseProjectController extends BasePages {


    protected function get_project_helper() : ProjectHelper {
        return ProjectHelper::get_project_helper();
    }
}