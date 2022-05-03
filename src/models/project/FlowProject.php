<?php

namespace app\models\project;

use app\models\project\levels\FlowProjectGitLevel;



/**
 * debug if needed
 * @uses \app\models\project\IFlowProject::raw_history()
 *
 * twig views use
 * @uses \app\models\project\IFlowProject::get_read_me_bb_code_with_paths()
 * @uses \app\models\project\IFlowProject::get_current_user_permissions()
 * @uses \app\models\project\IFlowProject::get_applied_tags()
 * @uses \app\models\project\IFlowProject::count_total_public_history()
 * @uses \app\models\project\IFlowProject::history()
 * @uses \app\models\project\IFlowProject::get_project_blurb()
 * @uses \app\models\project\IFlowProject::get_created_ts()
 */
class FlowProject extends FlowProjectGitLevel {



}