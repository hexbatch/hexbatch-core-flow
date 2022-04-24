<?php
namespace app\models\project;

use app\helpers\ProjectHelper;
use app\hexlet\RecursiveClasses;
use app\models\base\FlowBase;
use Exception;
use LogicException;
use RuntimeException;

class FlowProjectFiles extends FlowBase {


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


    protected string $flow_project_guid;
    protected string $owner_user_guid;


    /**
     * @param string $repo_path
     * @throws Exception
     */
    public function create_project_repo(string $repo_path) {
        if (!is_readable($repo_path)) {
            $check =  mkdir($repo_path,0777,true);
            if (!$check) {
                throw new RuntimeException("Could not create the directory of $repo_path");
            }

            if (!is_readable($repo_path)) {
                throw new RuntimeException("Could not make a readable directory of $repo_path");
            }
        }
        //have a directory, now need to add the .gitignore
        $ignore = file_get_contents(__DIR__. DIRECTORY_SEPARATOR . 'repo'. DIRECTORY_SEPARATOR . 'gitignore.txt');
        file_put_contents($repo_path.DIRECTORY_SEPARATOR.'.gitignore',$ignore);
        $this->do_git_command("init");
    }

    /**
     * @param string $command
     * @param string|null $pre_command
     * @param  bool $b_include_git_word  default true
     * @return string
     * @throws Exception
     */
    public  function do_git_command( string $command,bool $b_include_git_word = true,?string $pre_command = null) : string {
        $dir = $this->get_project_directory();
        if (!$dir) {
            throw new RuntimeException("Project Directory is not created yet");
        }
        return FlowGitHistory::do_git_command($dir,$command,$b_include_git_word,$pre_command);
    }

    public function __construct(string $flow_project_guid,string $owner_user_guid)
    {
        $this->owner_user_guid = $owner_user_guid;
        $this->flow_project_guid = $flow_project_guid;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_projects_base_directory() : string {
        $check =  static::$container->get('settings')->project->parent_directory;
        if (!is_readable($check)) {
            throw new RuntimeException("The directory of $check is not readable");
        }
        return $check;
    }

    /**
     * @param bool $b_already_created optional, OUTREF, set to true if already created
     * @return string|null
     * @throws Exception
     */
    public function get_project_directory(?bool &$b_already_created = false) : ?string {
        if (empty($this->flow_project_guid) || empty($this->owner_user_guid)) {return null;}
        $check =  $this->get_projects_base_directory(). DIRECTORY_SEPARATOR .
            $this->owner_user_guid . DIRECTORY_SEPARATOR . $this->flow_project_guid;
        $b_already_created = true;
        if (!is_readable($check)) {
            $b_already_created = false;
            $this->create_project_repo($check);
        }
        $real = realpath($check);
        if (!$real) {
            throw new LogicException("Could not find project directory at $check");
        }
        return $real;
    }


    /**
     * @return string|null
     * @throws Exception
     */
    public function get_files_directory() : ?string {
        $project_directory = $this->get_project_directory();
        if (!$project_directory) {return null;}
        $resource_directory = $project_directory.DIRECTORY_SEPARATOR. static::REPO_FILES_DIRECTORY;
        $real = realpath($resource_directory);
        if (!$real) {
            $b_made = mkdir($resource_directory);
            if (!$b_made) {
                throw new RuntimeException("Cannot make files directory at $real");
            }
            $real = realpath($resource_directory);
        }
        if (!is_readable($real)) {
            throw new RuntimeException("Files directory is not readable at $real");
        }
        return $real;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function get_resource_directory() : ?string {
        $project_directory = $this->get_project_directory();
        if (!$project_directory) {return null;}
        $resource_directory = $project_directory.DIRECTORY_SEPARATOR. static::REPO_RESOURCES_DIRECTORY;
        $real = realpath($resource_directory);
        if (!$real) {
            $b_made = mkdir($resource_directory);
            if (!$b_made) {
                throw new RuntimeException("Cannot make resource directory at $real");
            }
            $real = realpath($resource_directory);
        }
        if (!is_readable($real)) {
            throw new RuntimeException("Resource directory is not readable at $real");
        }
        return $real;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_resource_url() : string {
        $base_resource_directory = '/' . $this->owner_user_guid .
            '/' . $this->flow_project_guid . '/' . static::REPO_RESOURCES_DIRECTORY ; //no slash at end
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return $root. $base_resource_directory;
    }


    /**
     * @return string
     * @throws Exception
     */
    public function get_files_url() : string {
        $base_resource_directory = '/' . $this->owner_user_guid .
            '/' . $this->flow_project_guid . '/' . static::REPO_FILES_DIRECTORY ; //no slash at end
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return $root. $base_resource_directory;
    }

    /**
     * returns array of full file paths of any resources (or protected files) found that is sharable (png,jpg,jpeg,pdf)
     * @return string[]
     * @throws
     */
    public function get_resource_file_paths(bool $b_get_protected_files_instead = false): array{
        if ($b_get_protected_files_instead) {
            $resource_directory = $this->get_files_directory();
        } else {
            $resource_directory = $this->get_resource_directory();
        }

        if (empty($resource_directory)) {return [];}
        $types_piped = implode('|',static::REPO_RESOURCES_VALID_TYPES);
        $pattern = "/.+($types_piped)\$/";
        $list = RecursiveClasses::rsearch_for_paths($resource_directory,$pattern);
        $ret = [];
        foreach ($list as $path) {
            if (!is_string($path)) {
                static::get_logger()->error("File path in resource directory is not a string",['$path'=>$path]);
                throw new LogicException("File path in resource directory is not a string");
            }

            $what = realpath($path);
            if ($what && is_readable($path)) {$ret[] = $what;}
        }
        return $ret;
    }


    /**
     * returns array of full url paths of any resources found that is sharable (png,jpg,jpeg,pdf)
     * @param array $resource_file_paths_given option to list
     * @return string[]
     * @throws Exception
     */
    public function get_resource_urls(array $resource_file_paths_given = []): array{
        $resource_files = $this->get_resource_file_paths();

        if (empty($resource_file_paths_given)) {
            $resource_files_used = $resource_files;
        } else {
            $resource_files_used = [];
            foreach ($resource_file_paths_given as $full_given_path) {
                if (in_array($full_given_path,$resource_files)) {
                    $resource_files_used[] = $full_given_path;
                }
            }
        }


        $base_resource_file_path = $this->get_resource_directory(); //no slash at end
        $base_project_url = $this->get_resource_url();
        $resource_urls = [];
        foreach ($resource_files_used as $full_path_file) {
            $full_url = str_replace($base_resource_file_path,$base_project_url,$full_path_file);
            $resource_urls[] = $full_url;
        }
        return $resource_urls;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_git_status(): array  {
        $what =  $this->do_git_command("status");
        return explode("\n",$what);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_head_commit_hash() : string {
        try {
            return $this->do_git_command('rev-parse HEAD');
        } catch (Exception $e) {
            return '';
        }
    }
}