<?php
namespace app\models\project;

use app\models\standard\IFlowTagStandardAttribute;
use app\models\tag\FlowTag;
use app\models\user\FlowUser;
use Psr\Http\Message\UploadedFileInterface;


interface IFlowProject {
    const TABLE_NAME = 'flow_projects';


    const FLOW_PROJECT_TYPE_TOP = 'top';
    const FLOW_PROJECT_TYPE_USER_HOME = 'user-page';

    const SPECIAL_FLAG_ADMIN = 'full_admin';


    const MAX_SIZE_READ_ME_IN_CHARACTERS = 4000000;

    const GIT_IMPORT_SETTING_NAME = 'git-import';
    const GIT_EXPORT_SETTING_NAME = 'git-export';

    //in the settings, the tag attribute of the same name as the setting points to the tag that holds the standard (or lack of it)
    const STANDARD_SETTINGS = [
        self::GIT_EXPORT_SETTING_NAME => [
            'standard_attribute_name'=>IFlowTagStandardAttribute::STD_ATTR_NAME_GIT,
            'tag_name' => 'git-settings'
        ],

        self::GIT_IMPORT_SETTING_NAME => [
            'standard_attribute_name'=>IFlowTagStandardAttribute::STD_ATTR_NAME_GIT,
            'tag_name' => 'git-settings'
        ],
    ];


    const REPO_FILES_DIRECTORY = 'files';
    const REPO_RESOURCES_DIRECTORY = 'resources';
    const REPO_RESOURCES_VALID_TYPES = [
        'png',
        'jpeg',
        'jpg',
        'pdf'
    ];

    const RESOURCE_PATH_STUB = '@resource@';
    const FILES_PATH_STUB = '@file@';


    public function get_readme_bb_code() : ?string ;
    public function get_project_blurb() : ?string ;
    public function get_project_title() : ?string ;
    public function get_project_guid() : ?string ;
    public function is_public() : ?bool ;
    public function get_created_ts() : ?int ;
    public function get_id() : ?int ;


    public function set_project_blurb(?string $blurb) : void ;
    public function set_project_title(?string $title) : void ;
    public function set_project_type(?string $type) : void ;
    public function set_public(bool $type) : void ;

    public function set_admin_user_id(?int $user_id) : void ;


    public function save(bool $b_do_transaction = true) : void;
    public function destroy_project(bool $b_do_transaction = true): void;

    public function get_current_user_permissions(): ?FlowProjectUser;
    public function set_current_user_permissions(?FlowProjectUser $v);

    /**
     * @return FlowUser[]
     */
    public function get_flow_project_users() : array;

    public function get_admin_user(): ?FlowUser;
    public function get_owner_user_guid() : ?string;

    public function get_read_me_bb_code_with_paths(): string;
    public function set_read_me(string $bb_code) : void;
    public function delete_project_directory() : void;
    public function get_html() : ?string;

    public function get_project_directory() : ?string;
    public function get_files_directory() : ?string;
    public function get_resource_directory() : ?string;
    public function get_files_url() : string;
    public function get_resource_url() : string;


    public static function create_project_from_upload(string $archive_file_path,string $flow_project_title) :IFlowProject;

    /**
     * @param bool $b_refresh  if true will not use previous value if set
     * @return FlowTag[]
     */
    function get_applied_tags(bool $b_refresh = false) : array;

    /**
     * @param bool $b_get_applied  if true will also get the applied in the set of tags found
     * @param bool $b_refresh  if true will not use previous value if set
     * @return FlowTag[]
     */
    function get_all_owned_tags_in_project(bool $b_get_applied = false,bool $b_refresh = false) : array;

    /**
     * Gets the tag that holds the setting data. This may be from another project
     * @param string $setting_name
     * @return FlowTag|null
     */
    public function get_setting_tag(string $setting_name) : ?FlowTag;

    public function get_setting_holder_tag(string $setting_name) : ?FlowTag;

    function import_pull_repo_from_git() :array;
    function apply_patch(string $patch_file_path) :array;

    public function push_repo() : array;
    public function do_tag_save_and_commit();
    public function commit_changes(string $commit_message,bool $b_commit = true,bool $b_log_message = false): void;
    public function reset_project_repo_files();
    public function get_tag_yaml_path() : string;
    public function raw_history(): array;
    public function count_total_public_history(bool $b_refresh= false) : int;

    public function get_head_commit_hash() : ?string;
    public function get_git_status(): array;
    public function do_git_command( string $command,bool $b_include_git_word = true,?string $pre_command = null) : string;

    /**
     * @param int|null $start_at
     * @param int|null $limit
     * @param bool $b_refresh , default false
     * @param bool $b_public , default false
     * @return FlowGitHistory[]
     */
    public function history(?int $start_at = null, ?int $limit = null,  bool $b_refresh= false, bool $b_public = false): array;


}