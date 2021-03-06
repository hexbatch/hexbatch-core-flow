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
 * no enumeration keys are not used on the front end
 *      (for example the css key for the css standard attribute, only colors are shown in value)
 *
 * a default key is one added by the code if the key or the key value is missing,
 *   once its added it will not change even if the code adds a different value for this in children or other tags
 *  default keys can optionally be volatile and required
 *
 * defaults for missing key values can be provided by the code, but it does not change the behavior or overwriting of the keys later
 *  if an attribute is saved with the key name, or has json with the key name, then that default is tossed out and the new one used
 *
 * If-others means the key is only used if other keys (that are not if-others) are there too
 *      This is useful if want to put put in a default but not create the meta if only the defaults are there
 *
 *  When tags load in their standard attributes, it will be from the table.
 *      If an ancestor was the last one to significantly change a standard attribute, then their standard is that one
 *          This ignores any ignored keys in their own (or inherited) attributes that have changed since then
 *
 *
 * Options for a standard attribute are: copy if supplied a column of [db-table,column] to write to the db table :
 *      output will be json string
 *
 * Standards must have converters with an array of the converter class to instantiate and, once created, a public non static method
 *      This should be inherited from BaseConverter and use the convert method
 *
 * Standards can have  gui pre-processing with a callable with key of  pre_process_for_gui
 *      There is no convention for this, except the callable needs to accept the interface here as its only param
 *          the output can return any object, or null, and this will be the new attribute value going to the gui
 *          But, this is only used when calling the method of preProcessForGui function below
 */
Interface IFlowTagStandardAttribute {


    # ------------- DEFINE key modifiers
    const OPTION_VOLATILE = 'volatile';
    const OPTION_REQUIRED = 'required'; //if required has a default, then put that first in the key attributes array

    const OPTION_DEFAULT = 'default';
    const OPTION_IF_OTHERS = 'if-others';

    const OPTION_NO_SERIALIZATION = 'no-serialization';
    const OPTION_NO_ENUMERATION = 'no-enumeration';

    const OPTION_NORMAL = 'normal'; //used in the writer for keys with no attribute, not used to define keys

    # ------------- DEFINE copy types

    const COPY_TYPE_DB_UPDATE_VALUE = 'db_update_value';



    # ------------- DEFINE the different standard attributes here



    # ------------- GIT
    const STD_ATTR_NAME_GIT = 'git';

    const GIT_KEY_SSH_KEY = 'git_ssh_key';
    const GIT_KEY_REPO_URL = 'git_url';
    const GIT_KEY_BRANCH = 'git_branch';
    const GIT_KEY_NOTES = 'git_notes';
    const GIT_KEY_WEB_PAGE = 'git_web_page';
    const GIT_KEY_AUTOMATE = 'git_automate_push';

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

            self::GIT_KEY_NOTES => [],
            self::GIT_KEY_WEB_PAGE => [],
            self::GIT_KEY_AUTOMATE => [
                self::OPTION_DEFAULT=> 'false',
            ]
        ],
        'name' => self::STD_ATTR_NAME_GIT,
        'converter' => ['app\models\standard\converters\GitConverter','convert'],
        'pre_process_for_gui' => ['app\models\standard\converters\GitConverter','pre_process_outbound'],
    ];


    # ------------- CSS

    const STD_ATTR_NAME_CSS = 'css';

    const CSS_KEY_COLOR = 'color';
    const CSS_KEY_BACKGROUND_COLOR = 'backgroundColor';
    const CSS_KEY_FONT_FAMILY = 'fontFamily';


    const STD_ATTR_TYPE_CSS = [
        'keys' => [
            self::CSS_KEY_BACKGROUND_COLOR => [] ,
            self::CSS_KEY_COLOR => [] ,
            self::CSS_KEY_FONT_FAMILY => []
        ],
        'name' => self::STD_ATTR_NAME_CSS,
        'converter' => ['app\models\standard\converters\CssConverter','convert'],
        'copy' => [
            'type'=> self::COPY_TYPE_DB_UPDATE_VALUE,
            'table'=>'flow_things',
            'id_column' => 'thing_guid',
            'id_value' => 'tag_guid',
            'target_column' => 'css_json',
            'target_cast' => 'JSON'
        ]
    ];

    # ------------ META

    const STD_ATTR_NAME_META = 'meta';

    const META_KEY_VERSION = 'meta_version';
    const META_KEY_DATETIME = 'meta_date_time';
    const META_KEY_AUTHOR = 'meta_author';
    const META_KEY_FIRST_NAME = 'meta_first_name';
    const META_KEY_LAST_NAME = 'meta_last_name';
    const META_KEY_PUBLIC_EMAIL = 'meta_public_email';
    const META_KEY_PICTURE_URL = 'meta_picture_url';
    const META_KEY_WEBSITE = 'meta_website';

    const STD_ATTR_TYPE_META = [
        'keys' => [
            self::META_KEY_VERSION => [

            ] ,
            self::META_KEY_DATETIME => [
                self::OPTION_DEFAULT=>['app\helpers\Utilities','generate_iso_time_stamp'],
                self::OPTION_IF_OTHERS => true
            ] ,
            self::META_KEY_AUTHOR => [] ,
            self::META_KEY_FIRST_NAME => [] ,
            self::META_KEY_LAST_NAME => [] ,
            self::META_KEY_PUBLIC_EMAIL => [] ,
            self::META_KEY_PICTURE_URL => [] ,
            self::META_KEY_WEBSITE => [] ,
        ],
        'name' => self::STD_ATTR_NAME_META,
        'converter' => ['app\models\standard\converters\MetaConverter','convert'],
        'pre_process_for_gui' => ['app\models\standard\converters\MetaConverter','pre_process_outbound'],
    ];



    const STANDARD_ATTRIBUTES = [
        self::STD_ATTR_NAME_META => self::STD_ATTR_TYPE_META ,
        self::STD_ATTR_NAME_CSS => self::STD_ATTR_TYPE_CSS ,
        self::STD_ATTR_NAME_GIT => self::STD_ATTR_TYPE_GIT
    ];



    public function getStandardValue() : ?object ;
    public function getStandardName() : string;
    public function getLastUpdatedTs() : int;
    public function getProjectGuid() : string ;
    public function getTagGuid() : string ;
    public function getTagId() : int ;
    public function getStandardGuid() : string;
    public function getStandardId() : int ;

    public function getStandardValueToArray() : array;

    /**
     * does nothing  if there is nothing to process set in interface for the standard
     * otherwise returns a copy that shows the changed
     * @return IFlowTagStandardAttribute
     */
    public function preProcessForGui() : IFlowTagStandardAttribute;

    public static function getStandardAttributeKeys(string $name, bool $b_ignore_non_enumerated = true) : array;
    public static function getStandardAttributeNames() : array;
    public static function isNameKey(string $key_name,bool $is_also_protected = false ) : bool;
    public static function does_key_have_truthful_attribute(string $standard_name,string $target_key_name,string $attribute_name ) : bool;


    /**
     * gets hash with guid of tag as key, and array of standard attributes as value
     * (reads these from db)
     * @param FlowTag[] $flow_tags
     * @return array<string,IFlowTagStandardAttribute[]> mapped to tag guid
     */
    public  static function read_standard_attributes_of_tags(array $flow_tags) : array;

    /**
     * @param string|string[] $project_guid
     * @return array<string,IFlowTagStandardAttribute[]>  mapped to project guid
     */
    public  static function read_standard_attributes_of_projects(array|string $project_guid) : array;

    /**
     * @param string $user_name_email_or_guid  (email, username or guid)
     * @param bool $b_user_project_only default true
     * @return IFlowTagStandardAttribute[]  flat array of user's project's guids
     */
    public  static function read_standard_attributes_of_user(string $user_name_email_or_guid,bool $b_user_project_only = true) : array;

    /**
     * Writes standard attributes to db
     * @param FlowTag[] $flow_tags
     * @return array returns the standard attributes written
     */
    public  static function write_standard_attributes(array $flow_tags) : array;


}