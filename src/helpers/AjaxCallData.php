<?php

namespace app\helpers;

use app\models\project\FlowProjectUser;
use app\models\project\IFlowProject;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagAttribute;
use InvalidArgumentException;
use stdClass;

class AjaxCallData {
    public ?string $note = null;

    public ?stdClass $args = null;
    public ?IFlowProject $project;
    public ?array $new_token = [];

    const OPTION_MAKE_NEW_TOKEN = 'make_new_token';
    const OPTION_VALIDATE_TOKEN = 'validate_token';
    const OPTION_IS_AJAX = 'is_ajax';
    const OPTION_GET_APPLIED = 'get_applied';
    const OPTION_ALLOW_EMPTY_BODY = 'all_empty_body';

    /**
     * @var string[]
     */
    protected array $options = [];

    public function has_option(string $what) : bool {
        return in_array($what,$this->options);
    }

    /**
     * @var FlowTag|null $tag
     */
    public ?FlowTag $tag = null;


    /**
     * @var FlowTagAttribute|null $attribute
     */
    public ?FlowTagAttribute $attribute = null;

    /**
     * @var FlowAppliedTag|null $applied
     */
    public ?FlowAppliedTag $applied = null;

    public ?string $permission_mode;


    function __construct(array $options = [], ?stdClass $args = null ,?IFlowProject $project = null ,?array $new_token = null ){
        $this->args = $args;
        $this->project = $project;
        $this->new_token = $new_token;

        $this->tag = null;
        $this->attribute = null;
        $this->applied = null;

        $this->options = [];
        $this->note = null;
        $this->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;

        foreach ($options as $opt) {
            switch ($opt) {
                case static::OPTION_MAKE_NEW_TOKEN:
                case static::OPTION_VALIDATE_TOKEN:
                case static::OPTION_GET_APPLIED:
                case static::OPTION_ALLOW_EMPTY_BODY:
                case static::OPTION_IS_AJAX:
                {
                    $this->options[] = $opt;
                    break;
                }
                default: {
                    throw new InvalidArgumentException("[AjaxCallData] Option of $opt not recognized");
                }
            }
        }
    }
}