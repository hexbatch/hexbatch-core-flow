<?php

namespace app\controllers\entry;

use app\models\entry\FlowEntry;
use app\models\entry\FlowEntrySearchParams;
use app\models\project\FlowProject;
use InvalidArgumentException;
use stdClass;

class FlowEntryCallData {

    public ?string $note = null;

    public ?stdClass $args = null;
    public ?FlowProject $project;

    /**
     * @var FlowEntry[] $entry_array
     */
    public array $entry_array;

    public ?array $new_token = [];

    public bool $is_ajax_call = false;

    public ?FlowEntrySearchParams $search_used = null;

    const OPTION_MAKE_NEW_TOKEN = 'make_new_token';
    const OPTION_VALIDATE_TOKEN = 'validate_token';
    const OPTION_ENFORCE_AJAX = 'is_ajax';
    const OPTION_LIMIT_SEARCH_TO_PROJECT = 'limit_search_to_project';
    const OPTION_NO_CHILDREN_IN_SEARCH = 'no_children_in_search';

    const ALL_OPTIONS = [
      self::OPTION_LIMIT_SEARCH_TO_PROJECT,
      self::OPTION_NO_CHILDREN_IN_SEARCH,
      self::OPTION_ENFORCE_AJAX,
      self::OPTION_VALIDATE_TOKEN,
      self::OPTION_MAKE_NEW_TOKEN,

    ];

    /**
     * @var string[]
     */
    protected array $options = [];

    public function has_option(string $what) : bool {
        return in_array($what,$this->options);
    }

    public function set_option(string $what)  {
        if ( !in_array($what,static::ALL_OPTIONS)) {
            throw new InvalidArgumentException("[FlowEntryCallData] Option name not recognized while setting $what");
        }
        if (!$this->has_option($what)) {
            $this->options[] = $what;
        }
    }



    function __construct(array $options = [], ?stdClass $args = null ,?FlowProject $project = null ,?array $new_token = null ){
        $this->args = $args;
        $this->project = $project;
        $this->new_token = $new_token;

        $this->entry_array = [];

        $this->options = [];
        $this->note = null;
        $this->is_ajax_call = false;
        $this->search_used = null;

        foreach ($options as $opt) {
            switch ($opt) {
                case static::OPTION_MAKE_NEW_TOKEN:
                case static::OPTION_VALIDATE_TOKEN:
                case static::OPTION_ENFORCE_AJAX:
                case static::OPTION_LIMIT_SEARCH_TO_PROJECT:
                {
                    $this->options[] = $opt;
                    break;
                }
                default: {
                    throw new InvalidArgumentException("[FlowEntryCallData] Option of $opt not recognized");
                }
            }
        }
    }
}