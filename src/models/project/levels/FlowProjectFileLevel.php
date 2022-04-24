<?php
namespace app\models\project\levels;

use app\helpers\ProjectHelper;
use app\hexlet\JsonHelper;
use app\hexlet\RecursiveClasses;
use app\models\project\IFlowProject;
use Exception;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

abstract class FlowProjectFileLevel extends FlowProjectUserLevelLevel {

    protected ?string $flow_project_readme_html;




    /**
     * @param null|array|object $object
     * @throws Exception
     */
    public function __construct($object=null)
    {
        parent::__construct($object);
        $this->flow_project_readme_html = null;

        $this->get_html(); //sets the html var
    }

    /**
     * @return string|null
     * @throws Exception
     */
    protected function get_html_path() : ?string{
        $dir = $this->get_project_directory();
        if (empty($dir)) {return null;}
        $path = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_html.html';
        return $path;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function get_html() : ?string {
        if (!$this->flow_project_readme_html) {
            $path = $this->get_html_path();
            if (is_readable($path)){
                $this->flow_project_readme_html = file_get_contents($this->get_html_path());
                if ($this->flow_project_readme_html === false) {
                    throw new RuntimeException("Project html path exists but could not read");
                }
            } else {
                $this->flow_project_readme_html = null;
            }

        }
        return $this->flow_project_readme_html;

    }



    /**
     * @return void
     * @throws Exception
     */
    public function delete_project_directory() : void {
        $folder_to_remove = $this->get_project_directory();
        if ($folder_to_remove) {
            RecursiveClasses::rrmdir($folder_to_remove);
        }
    }


    /**
     * files not written until save called
     * @param string $bb_code
     * @throws Exception
     */
    public function set_read_me(string $bb_code) : void  {
        if (mb_strlen($bb_code) > IFlowProject::MAX_SIZE_READ_ME_IN_CHARACTERS) {
            throw new InvalidArgumentException("bb code is too large");
        }
        $bb_code = JsonHelper::to_utf8($bb_code);
        $origonal_bb_code = $bb_code;

        $this->flow_project_readme_bb_code = ProjectHelper::get_project_helper()->
        stub_from_file_paths($this,$bb_code);


        //may need to convert from the stubs back to the full paths for the html !
        $nu_read_me = ProjectHelper::get_project_helper()->
        stub_to_file_paths($this,$origonal_bb_code);

        $this->flow_project_readme_html = JsonHelper::html_from_bb_code($nu_read_me);
        $this->flow_project_readme = str_replace('&nbsp;',' ',strip_tags($this->flow_project_readme_html));
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_read_me_bb_code_with_paths(): string {

        $resource_url = $this->get_resource_url().'/';
        $read_me_full = str_replace(IFlowProject::RESOURCE_PATH_STUB,$resource_url,$this->flow_project_readme_bb_code);

        $file_url = $this->get_files_url().'/';
        $read_me_full = str_replace(IFlowProject::FILES_PATH_STUB,$file_url,$read_me_full);

        return $read_me_full;
    }

    /**
     * @param $command
     * @return string
     * @throws Exception
     */
    protected function do_project_directory_command($command) :string  {
        $directory = $this->get_project_directory();
        $full_command = "cd $directory && $command";
        exec($full_command,$output,$result_code);
        if ($result_code) {
            throw new RuntimeException("Cannot do $command,  returned code of $result_code : " . implode("\n",$output));
        }
        return implode("\n",$output);
    }

    /**
     * @param bool $b_do_transaction
     * @return void
     * @throws Exception
     */
    public function destroy_project(bool $b_do_transaction = true) : void {

        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            parent::destroy_project(false);
            $this->delete_project_directory();
            if ($b_do_transaction && $db->inTransaction()) { $db->commit();}
        } catch (Exception $e) {
            if ($b_do_transaction && $db->inTransaction()) { $db->rollBack();}
            throw $e;
        }

    }

    public function save(bool $b_do_transaction = true): void
    {
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            parent::save(false);


            $dir = $this->get_project_directory($b_already_created);


            $read_me_path_bb = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_bb_code.bbcode';
            $read_me_path_html = $this->get_html_path();
            $blurb_path = $dir . DIRECTORY_SEPARATOR . 'flow_project_blurb';
            $title_path = $dir . DIRECTORY_SEPARATOR . 'flow_project_title';

            $b_ok = file_put_contents($read_me_path_bb,$this->flow_project_readme_bb_code);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $read_me_path_bb");}

            $b_ok = file_put_contents($read_me_path_html,$this->flow_project_readme_html);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $read_me_path_html");}

            $b_ok = file_put_contents($blurb_path,$this->flow_project_blurb);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $blurb_path");}

            $b_ok = file_put_contents($title_path,$this->flow_project_title);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $title_path");}

            if ($b_do_transaction && $db->inTransaction()) { $db->commit();}
        } catch (Exception $e) {
            if ($b_do_transaction && $db->inTransaction()) { $db->rollBack();}
            throw $e;
        }
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
        $types_piped = implode('|',IFlowProject::REPO_RESOURCES_VALID_TYPES);
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
     * @return string
     * @throws Exception
     */
    public function get_resource_url() : string {
        $base_resource_directory = '/' . $this->get_owner_user_guid() .
            '/' . $this->flow_project_guid . '/' . IFlowProject::REPO_RESOURCES_DIRECTORY ; //no slash at end
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return $root. $base_resource_directory;
    }


    public static function calculate_resource_url(string $owner_user_guid,string $flow_project_guid) : string {
        $base_resource_directory = '/' . $owner_user_guid .
            '/' . $flow_project_guid . '/' . IFlowProject::REPO_RESOURCES_DIRECTORY ; //no slash at end
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return $root. $base_resource_directory;
    }


    /**
     * @return string
     * @throws Exception
     */
    public function get_files_url() : string {
        $base_resource_directory = '/' . $this->get_owner_user_guid() .
            '/' . $this->flow_project_guid . '/' . IFlowProject::REPO_FILES_DIRECTORY ; //no slash at end
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return $root. $base_resource_directory;
    }

    public static function calculate_files_url(string $owner_user_guid,string $flow_project_guid) : string {
        $base_resource_directory = '/' . $owner_user_guid .
            '/' . $flow_project_guid . '/' . IFlowProject::REPO_FILES_DIRECTORY ; //no slash at end
        $root = ProjectHelper::get_project_helper()->get_root_url();
        return $root. $base_resource_directory;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function get_resource_directory() : ?string {
        $project_directory = $this->get_project_directory();
        if (!$project_directory) {return null;}
        $resource_directory = $project_directory.DIRECTORY_SEPARATOR. IFlowProject::REPO_RESOURCES_DIRECTORY;
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
     * @return string|null
     * @throws Exception
     */
    public function get_files_directory() : ?string {
        $project_directory = $this->get_project_directory();
        if (!$project_directory) {return null;}
        $resource_directory = $project_directory.DIRECTORY_SEPARATOR. IFlowProject::REPO_FILES_DIRECTORY;
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
     * @return string
     * @throws Exception
     */
    protected function get_projects_base_directory() : string {
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
        if (empty($this->flow_project_guid) || empty($this->get_owner_user_guid())) {return null;}
        $check =  $this->get_projects_base_directory(). DIRECTORY_SEPARATOR .
            $this->get_owner_user_guid() . DIRECTORY_SEPARATOR . $this->flow_project_guid;
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
     * @param string $repo_path
     * @throws Exception
     */
    protected function create_project_repo(string $repo_path) {
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
    }


}