<?php

namespace app\helpers;

use app\models\base\SearchParamBase;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\project\FlowProject;
use app\models\project\FlowProjectFiles;
use app\models\project\FlowProjectSearch;
use app\models\project\FlowProjectSearchParams;
use app\models\project\FlowProjectUser;
use app\models\user\FlowUser;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class ProjectHelper extends BaseHelper {


    public static function get_project_helper() : ProjectHelper {
        try {
            return static::get_container()->get('projectHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }

    }

    public function get_root_url() : string {

        if (!array_key_exists('HTTP_HOST',$_SERVER)) {
            throw new RuntimeException('HTTP_HOST key not in $_Server');
        }
        $root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ;
        return $root;
    }

    /**
     * @param ServerRequestInterface|NULL $request
     * @param string|null $user_name_or_guid
     * @param string $project_name
     * @param string $permission read|write|admin
     * @return FlowProject|null
     * @throws
     */
    public  function get_project_with_permissions (
        ?ServerRequestInterface $request,?string $user_name_or_guid, string $project_name, string $permission) : ?FlowProject
    {

        try {
            $project = $this->find_one($project_name,$user_name_or_guid,$permission,$this->user->flow_user_id);
        } catch (InvalidArgumentException $not_found) {
            if ($request) {
                throw new HttpNotFoundException($request,sprintf("Cannot Find Project %s",$project_name));
            } else {
                throw new InvalidArgumentException(sprintf("Cannot Find Project %s",$project_name));
            }

        }

        if ($this->user->flow_user_id) {
            $user_permissions = FlowUser::find_users_by_project(true,
                $project->flow_project_guid, null, true, $this->user->flow_user_guid);

            if (empty($user_permissions)) {
                throw new InvalidArgumentException("No permissions set for this");
            }
            $permissions_array = $user_permissions[0]->get_permissions();
            if (empty($permissions_array)) {
                throw new InvalidArgumentException("No permissions found, although in project");
            }
            $project_user = $permissions_array[0];

            $project->set_current_user_permissions($project_user);
        }


        return $project;
    }

    /**
     * get a project without a user if supply an id or guid
     * or get a project based on any number of guid, and name combinations for project and user
     * @param ?string $project_title_guid_or_id
     * @param ?string $user_name_guid_or_id
     * @param string|null $permission_type
     * @param null $permission_user_check
     * @return FlowProject|null
     * @throws Exception
     */
    public function find_one(?string $project_title_guid_or_id, ?string $user_name_guid_or_id = null,
                             ?string$permission_type=null, $permission_user_check = null ): ?FlowProject
    {
        if (!in_array($permission_type,FlowProjectUser::PERMISSION_COLUMNS)) {
            throw new LogicException("Wrong permission type here ".$permission_type);
        }
        $params = new FlowProjectSearchParams();
        switch ($permission_type) {
            case FlowProjectUser::PERMISSION_COLUMN_READ: {
                $params->setCanRead(true);
                break;
            }
            case FlowProjectUser::PERMISSION_COLUMN_WRITE: {
                $params->setCanWrite(true);
                break;
            }
            case FlowProjectUser::PERMISSION_COLUMN_ADMIN: {
                $params->setCanAdmin(true);
                break;
            }
        }
        if ($permission_type) {
            $params->setPermissionUserNameOrGuidOrId($permission_user_check);
        }

        $params->addProjectTitleGuidOrId($project_title_guid_or_id);
        $params->setOwnerUserNameOrGuidOrId($user_name_guid_or_id);
        $what = FlowProjectSearch::find_projects($params);
        if (empty($what)) {
            throw new InvalidArgumentException("Project Not Found");
        }
        return $what[0]??null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $project_guid
     * @return FlowProject
     * @throws HttpNotFoundException
     * @throws Exception
     */
    public function copy_project_from_guid(ServerRequestInterface $request,string $project_guid) : FlowProject {

        $origonal_project = $this->get_project_with_permissions($request,null, $project_guid, FlowProjectUser::PERMISSION_COLUMN_READ);

        if (!$origonal_project) {
            throw new HttpNotFoundException($request,"Project $project_guid Not Found");
        }
        $args = $request->getParsedBody();

        $new_project = null;
        try {
            $this->get_connection()->beginTransaction();
            $new_project = new FlowProject();
            $new_project->flow_project_type = FlowProject::FLOW_PROJECT_TYPE_TOP;
            $new_project->parent_flow_project_id = null;
            $new_project->admin_flow_user_id = FlowUser::get_logged_in_user()->flow_user_id;

            $new_project->flow_project_title = $args['flow_project_title']?? $origonal_project->flow_project_title;
            $new_project->flow_project_blurb = $origonal_project->flow_project_blurb;
            $new_project->is_public = $origonal_project->is_public;
            $new_project->save(false); //save first to get the directory ok
            $new_project->set_read_me($origonal_project->flow_project_readme_bb_code);

            $new_project->save(false);

            /**
             * @var array<string,string>
             */
            $guid_map = [];
            $guid_map[$origonal_project->flow_project_guid] = $new_project->flow_project_guid;

            //copy entries
            $entry_search_params = new FlowEntrySearchParams();
            $entry_search_params->owning_project_guid = $origonal_project->flow_project_guid;
            $entry_search_params->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
            $entries = FlowEntrySearch::search($entry_search_params);
            foreach ($entries as $entry) {
                $new_entry = $entry->clone_with_missing_data($origonal_project,$new_project);
                $new_entry->save_entry();
                $guid_map[$entry->get_guid()] = $new_entry->get_guid();
            }

            if ($this->get_current_user()->flow_user_guid !== $origonal_project->get_admin_user()->flow_user_guid) {
                $guid_map[$origonal_project->get_admin_user()->flow_user_guid] = $this->get_current_user()->flow_user_guid;
            }


            //copy tags
            $tags = $origonal_project->get_all_owned_tags_in_project(true,true);
            foreach ($tags as $tag) {
                if ($tag->parent_tag_guid) {
                    if (!array_key_exists($tag->parent_tag_guid,$guid_map)) {
                        throw new LogicException(sprintf("Parent tag of %s does not have a new guid",$tag->parent_tag_guid));
                    }
                }

                $new_tag = $tag->clone_change_project($guid_map);
                $guid_map[$tag->flow_tag_guid] = $new_tag->flow_tag_guid;
            }
            $new_project->save_tag_yaml_and_commit();



            //copy resource folder
            $original_file_resource_path = $origonal_project->getFlowProjectFiles()->get_resource_directory();
            $new_file_resource_path = $new_project->getFlowProjectFiles()->get_resource_directory();
            //cp -rT src target
            $this->do_command("cp -rT $original_file_resource_path $new_file_resource_path");
            $find_files = $new_project->getFlowProjectFiles()->get_resource_file_paths();
            if (count($find_files)) {
                $new_project->commit_changes("Added resources from original project");
            }

            //copy files folder
            $original_file_resource_path = $origonal_project->getFlowProjectFiles()->get_files_directory();
            $new_file_resource_path = $new_project->getFlowProjectFiles()->get_files_directory();
            //cp -rT src target
            $this->do_command("cp -rT $original_file_resource_path $new_file_resource_path");
            $find_files = $new_project->getFlowProjectFiles()->get_resource_file_paths(true);
            if (count($find_files)) {
                $new_project->commit_changes("Added protected files from original project");
            }
            if ($this->get_connection()->inTransaction()) {
                $this->get_connection()->commit();
            }
            return $new_project;
        } catch (Exception $e ) {
            if ($new_project) {$new_project->delete_project_directory();}
            if ($this->get_connection()->inTransaction()) {
                $this->get_connection()->rollBack();
            }
            throw $e;
        }
    }


    /**
     * @param ServerRequestInterface $request
     * @param string $file_form_name

     * @throws
     * @return UploadedFileInterface
     */
    public function find_and_move_uploaded_file(ServerRequestInterface $request,string $file_form_name) : UploadedFileInterface {
        $uploadedFiles = $request->getUploadedFiles();
        if (!array_key_exists($file_form_name,$uploadedFiles)) {
            throw new HttpBadRequestException($request,"Need the file named $file_form_name");
        }
        // handle single input with single file upload
        /**
         * @var UploadedFileInterface $uploadedFile
         */
        $uploadedFile = $uploadedFiles[$file_form_name];
        $file_name = $uploadedFile->getClientFilename();
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $phpFileUploadErrors = array(
                0 => 'There is no error, the file uploaded with success',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.',
            );
            throw new RuntimeException($request,"File Error $file_name : ". $phpFileUploadErrors[$uploadedFile->getError()] );
        }

        return $uploadedFile;
    }


    /**
     * @param string $command
     * @return string
     * @throws Exception
     */
    public function do_command(string $command) :string  {
        exec($command,$output,$result_code);
        if ($result_code) {
            throw new RuntimeException("Cannot do $command,  returned code of $result_code : " . implode("\n",$output));
        }
        return implode("\n",$output);
    }


    /**
     * @param int|null $flow_user_id
     * @return FlowProject[]
     * @throws
     */
    public function get_all_top_projects(?int $flow_user_id) : array {


        try {
            $params = new FlowProjectSearchParams();
            $params->setFlowProjectType(FlowProject::FLOW_PROJECT_TYPE_TOP);
            $params->setPermissionUserNameOrGuidOrId($flow_user_id);
            $params->setCanRead(true);
            $params->setPage(1);
            $params->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
            $ret = FlowProjectSearch::find_projects($params);

            if ($this->user->flow_user_id) {
                foreach ($ret as $project) {
                    $user_permissions = FlowUser::find_users_by_project(true,
                        $project->flow_project_guid, null, true, $this->user->flow_user_guid);
                    if (empty($user_permissions)) {
                        throw new InvalidArgumentException("No permissions set for this");
                    }
                    $permissions_array = $user_permissions[0]->get_permissions();
                    if (empty($permissions_array)) {
                        throw new InvalidArgumentException("No permissions found, although in project");
                    }
                    $project_user = $permissions_array[0];

                    $project->set_current_user_permissions($project_user);

                }
            }
            return $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("[get_all_top_projects] error ",['exception'=>$e]);
            throw $e;
        }


    }

    /**
     * @param FlowProjectFiles $project_files
     * @param string|null $text
     * @return string|null
     * @throws Exception
     */
    public function stub_from_file_paths(FlowProjectFiles $project_files, string $text) : string {
        if (!$text) {return $text;}
        $start_of_resources_url = $project_files->get_resource_url() . '/';

        $text =
            str_replace($start_of_resources_url,FlowProjectFiles::RESOURCE_PATH_STUB,$text);


        $start_of_files_url = $project_files->get_files_url() . '/';
        $text =
            str_replace($start_of_files_url,FlowProjectFiles::FILES_PATH_STUB,$text);
        return $text;

    }

    /**
     * @param FlowProjectFiles $project_files
     * @param string|null $text
     * @return string|null
     * @throws Exception
     */
    public function stub_to_file_paths(FlowProjectFiles $project_files, string $text) : string {
        if (!$text) {return $text;}
        $start_of_resources_url = $project_files->get_resource_url() . '/';
        $start_of_files_url = $project_files->get_files_url() . '/';
        $text = str_replace(FlowProjectFiles::RESOURCE_PATH_STUB,$start_of_resources_url,$text);
        $text = str_replace(FlowProjectFiles::FILES_PATH_STUB,$start_of_files_url,$text);
        return $text;
    }

    public function get_allowed_git_sites() : array {
        $program = $this->get_settings()->git ?? (object)[];
        return $program->supported_hosts ?? [];
    }


}