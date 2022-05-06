<?php

namespace app\helpers;

use app\models\entry\FlowEntrySearchParams;
use app\models\entry\IFlowEntry;
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
    const OPTION_ENFORCE_AJAX = 'is_ajax';
    const OPTION_GET_APPLIED = 'get_applied';
    const OPTION_ALLOW_EMPTY_BODY = 'all_empty_body';
    const OPTION_ALLOW_NO_PROJECT_HASH = 'allow_no_project_hash';



    const OPTION_LIMIT_SEARCH_TO_PROJECT = 'limit_search_to_project';
    const OPTION_NO_CHILDREN_IN_SEARCH = 'no_children_in_search';

    const ALL_OPTIONS = [
        self::OPTION_LIMIT_SEARCH_TO_PROJECT,
        self::OPTION_NO_CHILDREN_IN_SEARCH,
        self::OPTION_ENFORCE_AJAX,
        self::OPTION_VALIDATE_TOKEN,
        self::OPTION_MAKE_NEW_TOKEN,
        self::OPTION_GET_APPLIED,
        self::OPTION_ALLOW_EMPTY_BODY,
        self::OPTION_ALLOW_NO_PROJECT_HASH

    ];

    /**
     * @var string[]
     */
    protected array $options = [];

    public function has_option(string $what) : bool {
        return in_array($what,$this->options);
    }

    public function set_option(string $what): void {
        if ( !in_array($what,static::ALL_OPTIONS)) {
            throw new InvalidArgumentException("[AjaxCallData] Option name not recognized while setting $what");
        }
        if (!$this->has_option($what)) {
            $this->options[] = $what;
        }
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

    /**
     * @var IFlowEntry[] $entry_array
     */
    public array $entry_array;

    /**
     * @var IFlowEntry|null $entry
     */
    public ?IFlowEntry $entry;


    public ?FlowEntrySearchParams $entry_search_params_used = null;

    public function get_token_with_project_hash(?IFlowProject $p) : array  {
        if (empty($this->new_token)) {
            $base = [];
        } else {
            $base = $this->new_token;
        }
        $base['flow_project_git_hash'] =  $p?->get_head_commit_hash();
        return $base;
    }


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

        $this->entry = null;
        $this->entry_array = [];
        $this->entry_search_params_used = null;


        foreach ($options as $opt) {
            if (! in_array($opt,static::ALL_OPTIONS)) {
                throw new InvalidArgumentException("[AjaxCallData] Option of $opt not recognized in constructor");
            }
            $this->options[] = $opt;
        }
    }
}