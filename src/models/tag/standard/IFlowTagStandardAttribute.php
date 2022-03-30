<?php

namespace app\models\tag\standard;

use app\models\tag\FlowTag;
use app\models\tag\FlowTagAttribute;

/**
 * standard attributes are json formed from tag attributes of certain names that may or may not be inherited
 * The standard attribute has its own name, and its defined keys are the found attributes in the tags
 *          or the keys in the json of the tab attribute text
 *          attribute longs are ignored by standard attributes
 *
 * A standard attribute may require more than one key to be present and non empty before being labeled and saved
 *  when saving to the standard attributes table, unchanged standards in children are ignored
 *
 * each key in a standard attribute can optionally be  volatile, and/or required
 *  volatile keys apply to children making changes to these keys, changes here are not counted when deciding to save to table
 *  required keys need to be existing and non empty for the standard attribute to be defined for the tag
 *  keys that have neither of these can be missing if a standard attribute has more than one key,
 *          and if changed in child counts to being different and saved to table
 *
 * a constant key is one added by the code,
 *   once its added it will not change even if the code adds a different value for this in children or other tags
 *      The constant keys are only added making a new standard attribute or saving a standard attribute that is missing it
 *  Thus, constant keys are not provided by the attributes, even if they have the same name
 *  constant keys can optionally be volatile and ignored
 *
 * defaults for missing key values can be provided by the code, but it does not change the behavior or overwriting of the keys later
 *  if an attribute is saved with the key name, or has json with the key name, then that default is tossed out and the new one used
 *
 *  When tags load in their standard attributes, it will be from the table.
 *      If an ancestor was the last one to significantly change a standard attribute, then their standard is that one
 *          This ignores any ignored keys in their own (or inherited) attributes that have changed since then
 *
 *
 * Options for a standard attribute are: copy if supplied a column of [db-table,column] to write to the db table :
 *      output will be json string
 */
Interface IFlowTagStandardAttribute {


    # ------------- DEFINE key modifiers
    const OPTION_VOLATILE = 'volatile';
    const OPTION_REQUIRED = 'required';
    const OPTION_CONSTANT = 'constant';

    const KEY_DEFAULT = 'default';



    # ------------- DEFINE the different standard attributes here



    # ------------- GIT
    const STD_ATTR_NAME_GIT = 'git';

    const GIT_KEY_SSH_KEY = 'ssh_key';
    const GIT_KEY_REPO_URL = 'repo_url';
    const GIT_KEY_BRANCH = 'branch';
    const GIT_SITE = 'site';

    const STD_ATTR_TYPE_GIT = [
        'keys' => [
            self::GIT_KEY_BRANCH => [self::OPTION_REQUIRED, self::KEY_DEFAULT => 'master'],
            self::GIT_KEY_REPO_URL => [self::OPTION_REQUIRED],
            self::GIT_KEY_SSH_KEY => [],
            self::GIT_SITE => [],
        ],
        'name' => self::STD_ATTR_NAME_GIT,

        'copy' => ['flow_things','css_json']
    ];


    # ------------- CSS

    const STD_ATTR_NAME_CSS = 'css';
    const CSS_KEY_COLOR = 'color';
    const CSS_KEY_BACKGROUND_COLOR = 'background-color';

    /**
     * @used in template
     */
    const CSS_KEY_NAME_LIST = [
        self::CSS_KEY_BACKGROUND_COLOR  ,
        self::CSS_KEY_COLOR  ,
    ];

    const STD_ATTR_TYPE_CSS = [
        'keys' => [
            self::CSS_KEY_BACKGROUND_COLOR => [] ,
            self::CSS_KEY_COLOR => [] ,
        ],
        'name' => self::STD_ATTR_NAME_CSS
    ];

    # ------------ META

    const STD_ATTR_NAME_META = 'meta';

    const META_KEY_VERSION = 'meta_version';
    const META_KEY_DATETIME = 'meta_date_time';
    const META_KEY_AUTHOR = 'meta_author';

    const STD_ATTR_TYPE_META = [
        'keys' => [
            self::META_KEY_VERSION => [self::OPTION_REQUIRED, self::KEY_DEFAULT => ['app\helpers\Utilities','get_version_float']] ,
            self::META_KEY_DATETIME => [
                self::OPTION_CONSTANT=>['app\helpers\Utilities','generate_iso_time_stamp'],
                self::OPTION_VOLATILE
            ] ,
            self::META_KEY_AUTHOR => [] ,
        ],
        'name' => self::STD_ATTR_NAME_META
    ];

    const STANDARD_ATTRIBUTE_NAMES = [
        self::STD_ATTR_NAME_META,
        self::STD_ATTR_NAME_CSS,
        self::STD_ATTR_NAME_GIT

    ];

    const STANDARD_ATTRIBUTES = [ //todo references to this needs work
        self::STD_ATTR_TYPE_META ,
        self::STD_ATTR_TYPE_CSS ,
        self::STD_ATTR_TYPE_GIT
    ];



    public function get_standard_value() : object ;
    public function get_standard_name() : string;
    public function get_last_updated_ts() : int;
    public function get_tag_guid() : string ;

    public function get_standard_value_to_array() : array;


    /**
     * gets hash with guid of tag as key, and array of standard attributes as value
     * (reads these from db)
     * @param FlowTag[] $flow_tags
     * @return array<string,IFlowTagStandardAttribute[]>
     */
    public  static function read_standard_attributes(array $flow_tags) : array;

    /**
     * Writes standard attributes to db
     * @param FlowTag[] $flow_tags
     * @return int returns number of rows written or updated
     */
    public  static function write_standard_attributes(array $flow_tags) : int;


}