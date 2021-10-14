<?php

namespace app\models\tag;

use app\models\project\FlowProject;
use InvalidArgumentException;
use stdClass;

class FlowTagCallData {
    public ?string $note = null;

    public ?stdClass $args = null;
    public ?FlowProject $project;
    public ?array $new_token = [];

    const OPTION_MAKE_NEW_TOKEN = 'make_new_token';
    const OPTION_VALIDATE_TOKEN = 'validate_token';
    const OPTION_IS_AJAX = 'is_ajax';

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


    function __construct(array $options = [], ?stdClass $args = null ,?FlowProject $project = null ,?array $new_token = null ){
        $this->args = $args;
        $this->project = $project;
        $this->new_token = $new_token;

        $this->options = [];
        $this->note = null;

        foreach ($options as $opt) {
            switch ($opt) {
                case static::OPTION_MAKE_NEW_TOKEN:
                case static::OPTION_VALIDATE_TOKEN:
                case static::OPTION_IS_AJAX:
                {
                    $this->options[] = $opt;
                    break;
                }
                default: {
                    throw new InvalidArgumentException("[FlowTagCallData] Option of $opt not recognized");
                }
            }
        }
    }
}