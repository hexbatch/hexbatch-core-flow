<?php

namespace app\helpers;

use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\project\FlowProject;
use app\models\project\FlowProjectSearch;
use app\models\user\FlowUser;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use LogicException;
use PDO;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
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
     * @param ServerRequestInterface $request
     * @param string|null $user_name_or_guid
     * @param string $project_name
     * @param string $permission read|write|admin
     * @return FlowProject|null
     * @throws
     */
    public  function get_project_with_permissions (
        ServerRequestInterface $request,?string $user_name_or_guid, string $project_name, string $permission) : ?FlowProject
    {
        if ($permission !== 'read' && $permission !== 'write' && $permission !== 'admin') {
            throw new InvalidArgumentException("permission has to be read or write or admin");
        }

        $project = null;
        try {
            $project = $this->find_one($project_name,$user_name_or_guid);
        } catch (InvalidArgumentException $not_found) {
            throw new HttpNotFoundException($request,sprintf("Cannot Find Project %s",$project));
        }


        //return if public and nobody logged in
        if (empty($this->user->flow_user_id)) {
            if ($permission === 'read') {
                if ($project->is_public) {
                    return $project;
                }else {
                    throw new HttpForbiddenException($request,"Project is not public");
                }
            } else {
                throw new HttpForbiddenException($request,"Need to be logged in to edit this project");
            }
        }

        $user_permissions = FlowUser::find_users_by_project(true,
            $project->flow_project_guid,null,true,$this->user->flow_user_guid);

        if (empty($user_permissions)) {
            throw new InvalidArgumentException("No permissions set for this");
        }
        $permissions_array  = $user_permissions[0]->get_permissions();
        if (empty($permissions_array)) {
            throw new InvalidArgumentException("No permissions found, although in project");
        }
        $project_user = $permissions_array[0];

        $project->set_current_user_permissions($project_user);

        if ($permission === 'read') {
            if ($project->is_public) {
                return $project;
            } elseif (!$project_user->can_read) {
                throw new HttpForbiddenException($request,"Project cannot be viewed");
            }
        }
        else if ($permission === 'admin') {
            if ( ! $project_user->can_admin) {
                throw new HttpForbiddenException($request,"Only the admin can edit this part of the project $project_name");
            }
        } else if ($permission === 'write') {
            if ( ! $project_user->can_write) {
                throw new HttpForbiddenException($request,"You can view but not edit this project");
            }
        }

        return $project;
    }

    /**
     * get a project without a user if supply an id or guid
     * or get a project based on any number of guid, and name combinations for project and user
     * @param ?string $project_title_guid_or_id
     * @param ?string $user_name_guid_or_id
     * @return FlowProject|null
     * @throws
     */
    public function find_one(?string $project_title_guid_or_id, ?string $user_name_guid_or_id = null): ?FlowProject
    {
        $limit_projects = [];
        if (trim($project_title_guid_or_id)) {$limit_projects[] = trim($project_title_guid_or_id);}
        $what = FlowProjectSearch::find_projects($limit_projects,$user_name_guid_or_id);
        if (empty($what)) {
            throw new InvalidArgumentException("Project Not Found");
        }
        return $what[0]??null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $project_guid
     * @return FlowProject
     * @throws HttpForbiddenException
     * @throws HttpNotFoundException
     * @throws Exception
     */
    public function copy_project_from_guid(ServerRequestInterface $request,string $project_guid) : FlowProject {

        $origonal_project = $this->get_project_with_permissions($request,null, $project_guid, 'read');

        if (!$origonal_project) {
            throw new HttpNotFoundException($request,"Project $project_guid Not Found");
        }
        $args = $request->getParsedBody();

        try {
            $this->get_connection()->beginTransaction();
            $new_project = new FlowProject();
            $new_project->flow_project_type = FlowProject::FLOW_PROJECT_TYPE_TOP;
            $new_project->parent_flow_project_id = null;
            $new_project->admin_flow_user_id = FlowUser::get_logged_in_user()->flow_user_id;

            $new_project->flow_project_title = $args['flow_project_title']?? $origonal_project->flow_project_title;
            $new_project->flow_project_blurb = $origonal_project->flow_project_blurb;
            $new_project->is_public = $origonal_project->is_public;
            $new_project->set_read_me($origonal_project->flow_project_readme_bb_code);

            $new_project->save(false);
            //copy tags
            $tags = $origonal_project->get_all_owned_tags_in_project(true,true);
            $tag_parent_hash = [];
            foreach ($tags as $tag) {
                $new_parent_guid = null;
                if ($tag->parent_tag_guid) {
                    if (!array_key_exists($tag->parent_tag_guid,$tag_parent_hash)) {
                        throw new LogicException(sprintf("Parent tag of %s does not have a new guid",$tag->parent_tag_guid));
                    }
                    $new_parent_guid = $tag_parent_hash[$tag->parent_tag_guid];
                }

                $new_tag = $tag->clone_change_project($new_project->id,$new_parent_guid);
                $tag_parent_hash[$tag->flow_tag_guid] = $new_tag->flow_tag_guid;
            }
            $new_project->save_tag_yaml_and_commit(true);

            //copy entries
            $entry_search_params = new FlowEntrySearchParams();
            $entry_search_params->owning_project_guid = $origonal_project->flow_project_guid;
            $entry_search_params->set_page_size(FlowEntrySearchParams::UNLIMITED_RESULTS_PER_PAGE);
            $entries = FlowEntrySearch::search($entry_search_params);
            foreach ($entries as $entry) {
                $new_entry = $entry->clone_with_missing_data($origonal_project,$new_project);
                $new_entry->save_entry();
            }

            //copy resource folder
            $original_file_resource_path = $origonal_project->get_resource_directory();
            $new_file_resource_path = $new_project->get_resource_directory();
            //cp -rT src target
            $this->do_command("cp -rT $original_file_resource_path $new_file_resource_path");
            $find_files = $new_project->get_resource_file_paths();
            if (count($find_files)) {
                $new_project->commit_changes("Added resources from original project");
            }

            //copy files folder
            $original_file_resource_path = $origonal_project->get_resource_directory();
            $new_file_resource_path = $new_project->get_resource_directory();
            //cp -rT src target
            $this->do_command("cp -rT $original_file_resource_path $new_file_resource_path");
            $find_files = $new_project->get_resource_file_paths();
            if (count($find_files)) {
                $new_project->commit_changes("Added resources from original project");
            }
            if ($this->get_connection()->inTransaction()) {
                $this->get_connection()->commit();
            }
            return $new_project;
        } catch (Exception $e ) {
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
     * @param ServerRequestInterface $request
     * @param int $flow_user_id
     * @return FlowProject[]
     * @throws HttpForbiddenException
     * @throws HttpNotFoundException
     */
    public function get_all_top_projects(ServerRequestInterface $request,int $flow_user_id) : array {
        $db = $this->get_connection();
        $sql = "SELECT DISTINCT 
                    p.id, p.created_at_ts , HEX(u.flow_user_guid) as flow_user_guid, HEX(p.flow_project_guid) as flow_project_guid
                FROM flow_project_users perm 
                INNER JOIN flow_projects p ON p.id = perm.flow_project_id 
                INNER JOIN flow_users u ON u.id = perm.flow_user_id 
                WHERE perm.flow_user_id = ? 
                  AND perm.can_read > 0 
                  AND p.flow_project_type = ?
                ORDER BY  p.created_at_ts DESC ";

        try {
            $args =  [$flow_user_id,FlowProject::FLOW_PROJECT_TYPE_TOP];
            $what = $db->safeQuery($sql,$args, PDO::FETCH_OBJ);
            if (empty($what)) {
                return [];
            }
            /**
             * @var FlowProject[] $ret;
             */
            $ret = [];
            foreach ($what as $row) {
                $node = $this->get_project_with_permissions($request,null, $row->flow_project_guid, 'read');
                if ($node) {
                    $ret[] = $node;
                }
            }
            return $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("[get_all_top_projects] error ",['exception'=>$e]);
            throw $e;
        }


    }


}