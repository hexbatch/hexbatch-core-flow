<?php

namespace app\models\standard;

use app\models\tag\FlowTag;

/**
 * standard attributes are json formed from tag attributes of certain names that may or may not be inherited
 * The standard attribute has its own name, and its defined keys are the found attributes in the tags
 *          or the keys in the json of the tab attribute text
 *          attribute longs are ignored by standard attributes
 *
 * A standard attribute may require more than one key to be present and non empty before being labeled and saved
 *  when saving to the standard attributes table, unchanged standards in children are ignored
 *
 * each key in a standard attribute can optionally be  volatile, and/or required and/or no-serialization
 *  volatile keys apply to children making changes to these keys, changes here are not counted when deciding to save to table
 *
 * required keys need to be existing and non empty for the standard attribute to be defined for the tag
 *  keys that have neither of these can be missing if a standard attribute has more than one key,
 *          and if changed in child counts to being different and saved to table
 *
 * no serialization keys are not saved to json
 *
 * a default key is one added by the code if the key or the key value is missing,
 *   once its added it will not change even if the code adds a different value for this in children or other tags
 *  default keys can optionally be volatile and required
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
    const OPTION_REQUIRED = 'required'; //if required has a default, then put that first in the key attributes array

    const OPTION_DEFAULT = 'default';

    const OPTION_NO_SERIALIZATION = 'no-serialization';

    # ------------- DEFINE copy types

    const COPY_TYPE_DB_UPDATE_VALUE = 'db_update_value';



    # ------------- DEFINE the different standard attributes here



    # ------------- GIT
    const STD_ATTR_NAME_GIT = 'git';

    const GIT_KEY_SSH_KEY = 'ssh_key';
    const GIT_KEY_REPO_URL = 'repo_url';
    const GIT_KEY_BRANCH = 'branch';
    const GIT_SITE = 'site';

    const STD_ATTR_TYPE_GIT = [
        'keys' => [
            self::GIT_KEY_BRANCH => [
                self::OPTION_DEFAULT => 'master',
                self::OPTION_REQUIRED => true,

            ],
            self::GIT_KEY_REPO_URL => [
                self::OPTION_REQUIRED => true
            ],
            self::GIT_KEY_SSH_KEY => [
                self::OPTION_NO_SERIALIZATION => true
            ],
            self::GIT_SITE => [],
        ],
        'name' => self::STD_ATTR_NAME_GIT,
        'converter' => ['app\models\standard\converters\Git','convert'],
        'copy' => [
            'type'=> self::COPY_TYPE_DB_UPDATE_VALUE,
            'table'=>'flow_things',
            'id_column' => 'thing_guid',
            'id_value' => 'tag_guid',
            'target_column' => 'css_json'
        ]
    ];


    # ------------- CSS

    const STD_ATTR_NAME_CSS = 'css';

    const CSS_KEY_COLOR = 'color';
    const CSS_KEY_BACKGROUND_COLOR = 'background-color';


    const STD_ATTR_TYPE_CSS = [
        'keys' => [
            self::CSS_KEY_BACKGROUND_COLOR => [] ,
            self::CSS_KEY_COLOR => [] ,
        ],
        'name' => self::STD_ATTR_NAME_CSS,
        'converter' => ['app\models\standard\converters\Css','convert']
    ];

    # ------------ META

    const STD_ATTR_NAME_META = 'meta';

    const META_KEY_VERSION = 'meta_version';
    const META_KEY_DATETIME = 'meta_date_time';
    const META_KEY_AUTHOR = 'meta_author';

    const STD_ATTR_TYPE_META = [
        'keys' => [
            self::META_KEY_VERSION => [
                self::OPTION_DEFAULT => ['app\helpers\Utilities','get_version_float'],
                self::OPTION_REQUIRED => true,
            ] ,
            self::META_KEY_DATETIME => [
                self::OPTION_DEFAULT=>['app\helpers\Utilities','generate_iso_time_stamp'],
            ] ,
            self::META_KEY_AUTHOR => [
                self::OPTION_VOLATILE => true
            ] ,
        ],
        'name' => self::STD_ATTR_NAME_META,
        'converter' => ['app\models\standard\converters\Meta','convert']
    ];



    const STANDARD_ATTRIBUTES = [
        self::STD_ATTR_NAME_META => self::STD_ATTR_TYPE_META ,
        self::STD_ATTR_NAME_CSS => self::STD_ATTR_TYPE_CSS ,
        self::STD_ATTR_NAME_GIT => self::STD_ATTR_TYPE_GIT
    ];



    public function getStandardValue() : object ;
    public function getStandardName() : string;
    public function getLastUpdatedTs() : int;
    public function getTagGuid() : string ;
    public function getTagId() : int ;
    public function getStandardGuid() : string;
    public function getStandardId() : int ;

    public function getStandardValueToArray() : array;

    public static function getStandardAttributeKeys(string $name) : array;
    public static function getStandardAttributeNames() : array;

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
     * @return array returns the standard attributes written
     */
    public  static function write_standard_attributes(array $flow_tags) : array;


}