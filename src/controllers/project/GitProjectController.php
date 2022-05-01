<?php
namespace app\controllers\project;

use app\helpers\AjaxCallData;
use app\helpers\UserHelper;
use app\hexlet\GoodZipArchive;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\project\exceptions\NothingToPullException;
use app\models\project\FlowGitFile;
use app\models\project\FlowProject;
use app\models\project\FlowProjectUser;
use app\models\project\IFlowProject;
use app\models\standard\IFlowTagStandardAttribute;
use Exception;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Psr7\Stream;


class GitProjectController extends BaseProjectController {


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function download_export(ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name) :ResponseInterface {

        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }
            $project_title = $project->get_project_title();
            $temp_file_path = tempnam(sys_get_temp_dir(), 'git-zip-');
            $b_rename_ok = rename($temp_file_path, $temp_file_path .= '.zip');
            if (!$b_rename_ok) {
                throw new HttpInternalServerErrorException($request,"Cannot rename temp folder");
            }

            $project_directory_path = $project->get_project_directory();
            new GoodZipArchive($project_directory_path,    $temp_file_path,$project->get_project_title()) ;
            $file_size = filesize($temp_file_path);
            if (!$file_size) {
                throw new HttpInternalServerErrorException($request,"Cannot create zip folder for $project_title download");
            }

            $response = $response
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Length', $file_size)
                ->withHeader('Content-Disposition', "attachment; filename=$project_title.zip")
                ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->withHeader('Cache-Control', 'post-check=0, pre-check=0')
                ->withHeader('Pragma', 'no-cache')
                ->withBody((new Stream(fopen($temp_file_path, 'rb'))));

            return $response;

        } catch (Exception $e) {
            $this->logger->error("Could not download project zip",['exception'=>$e]);
            throw $e;
        }


    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function get_file_change(ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name) :ResponseInterface {


        try {
            $args = $request->getParsedBody();
            if (empty($args)) {
                throw new InvalidArgumentException("No data sent");
            }


            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }

            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);


            $file_path = $args['file_path'] ?? '';
            //if (!$file_path) {throw new InvalidArgumentException("File Path needs to be set");}
            $commit = $args['commit'] ?? '';
            if (!$commit) {throw new InvalidArgumentException("Commit needs to be set");}

            $b_show_all = (bool)intval($args['show_all'] ?? '1');

            $project_directory = $project->get_project_directory();
            $flow_file = new FlowGitFile($project_directory,$commit,$file_path);
            $diff = $flow_file->show_diff($b_show_all);

            $data = ['success'=>true,'message'=>'','diff'=>$diff,'token'=> null];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);


        } catch (Exception $e) {
            $data = ['success'=>false,'message'=>$e->getMessage(),'diff'=>null,'token'=> null];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @param ?int  $page
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function project_history( ServerRequestInterface $request,ResponseInterface $response,
                                     string $user_name, string $project_name,?int $page) :ResponseInterface {
        try {
            $project = $this->get_project_helper()->get_project_with_permissions(
                $request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            if (intval($page) < 1) {$page = 1;}

            $status_array = $project->get_git_status();

            $git_tags = UserHelper::get_user_helper()->
            get_user_tags_of_standard($this->user->flow_user_guid,IFlowTagStandardAttribute::STD_ATTR_NAME_GIT);



            $git_import_tag_setting = $project->get_setting_tag(IFlowProject::GIT_IMPORT_SETTING_NAME);
            $git_export_tag_setting = $project->get_setting_tag(IFlowProject::GIT_EXPORT_SETTING_NAME);



            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_history.twig',
                'page_title' => "History for Project $project_name",
                'page_description' => 'History',
                'history_page_number' => $page,
                'history_page_size' => 10,
                'project' => $project,
                'status' => $status_array,
                'git_tags' => $git_tags,
                'git_import_tag_setting' => $git_import_tag_setting,
                'git_export_tag_setting' => $git_export_tag_setting
            ]);



        } catch (Exception $e) {
            $this->logger->error("Could not render history page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * makes new project and then redirects to new project page
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $project_name
     * @return ResponseInterface
     * @throws HttpNotImplementedException
     * 
     */
    public function clone_project_from_git(ServerRequestInterface $request, ResponseInterface $response, string $project_name) :ResponseInterface {
        WillFunctions::will_do_nothing($response,$project_name);
        //todo implement project from git
        throw new HttpNotImplementedException($request,"clone_project_from_git not implemented yet");
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     */
    public function push_project(ServerRequestInterface $request,ResponseInterface $response,
                                        string $user_name, string $project_name): ResponseInterface
    {


        $git_out = [];
        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_IS_AJAX,
                AjaxCallData::OPTION_MAKE_NEW_TOKEN,
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'push_project';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;
            $call = $this->get_project_helper()->validate_ajax_call($option, $request, null, $user_name, $project_name);

            $project = $call->project;

            if (!$project) {
                throw new HttpNotFoundException($request, "[push_project] Project $project_name Not Found");
            }

            $git_out = $project->push_repo();


            $data = [
                'success'=>true,
                'message'=>'ok',
                'git_output'=>$git_out,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (Exception $e) {
            $this->logger->error("Could not push_project: ".$e->getMessage(),['exception'=>$e]);
            $data = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'git_output'=>$git_out,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     */
    public function pull_project(ServerRequestInterface $request,ResponseInterface $response,
                                 string $user_name, string $project_name): ResponseInterface
    {


        $git_out = [];
        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_IS_AJAX,
                AjaxCallData::OPTION_MAKE_NEW_TOKEN,
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'pull_project';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;
            $call = $this->get_project_helper()->validate_ajax_call($option, $request, null, $user_name, $project_name);

            $project = $call->project;

            if (!$project) {
                throw new HttpNotFoundException($request, "[pull_project] Project $project_name Not Found");
            }

            $git_out = $project->import_pull_repo_from_git();


            $data = [
                'success'=>true,
                'message'=>'ok',
                'git_output'=>$git_out,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        }
        catch (NothingToPullException $no_pull) {
            $data = [
                'success'=>false,
                'message'=>$no_pull->getMessage(),
                'git_output'=>$git_out,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }
        catch (Exception $e) {
            $this->logger->error("Could not pull_project: ".$e->getMessage(),['exception'=>$e]);
            $data = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'git_output'=>$git_out,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     *
     */
    public function patch_from_file(ServerRequestInterface $request, ResponseInterface $response,
                                    string                 $user_name, string $project_name) :ResponseInterface {

        $temp_file_name_with_extension = null;
        $patch_status = [];
        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'patch_from_file';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;
            $call = $this->get_project_helper()->validate_ajax_call($option, $request, 'yes', $user_name, $project_name);

            $project = $call->project;


            $file_upload = $this->get_project_helper()->pre_process_uploaded_file($request,'patch_file');
            $tmpfname = tempnam(sys_get_temp_dir(), $project->get_project_title().'-');
            $temp_file_name_with_extension  = $tmpfname. '.patch';
            $b_rename = rename($tmpfname, $temp_file_name_with_extension );
            if (!$b_rename) {throw new LogicException("Cannot rename $tmpfname, $temp_file_name_with_extension ");}
            $file_name = $file_upload->getClientFilename();
            $file_upload->moveTo($temp_file_name_with_extension);
            //do patch now
            $patch_status = $project->apply_patch($temp_file_name_with_extension);
            $success_message = "Applied patch from file $file_name to "  . $project->get_project_title();


            $data = [
                'success'=>true,
                'message'=>$success_message,
                'git_output'=>$patch_status,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        }
        catch (NothingToPullException $no_pull) {
            $data = [
                'success'=>false,
                'message'=>$no_pull->getMessage(),
                'git_output'=>$patch_status,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }
        catch (Exception $e) {
            $this->logger->error("Could not pull_project: ".$e->getMessage(),['exception'=>$e]);
            $data = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'git_output'=>$patch_status,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        } finally {
            if ($temp_file_name_with_extension) {unlink($temp_file_name_with_extension);}
        }

    }



    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     *
     */
    public function create_from_upload(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface {

        $temp_file_name_with_extension = null;
        $project = null;
        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'create_from_upload';

            $call = $this->get_project_helper()->validate_ajax_call($option, $request, 'yes');


            $file_upload = $this->get_project_helper()->pre_process_uploaded_file($request,'archive_file');
            $tmpfname = tempnam(sys_get_temp_dir(), 'project-upload-');

            $client_file_name = $file_upload->getClientFilename();
            $temp_file_name_with_extension  = $tmpfname. '-'. $client_file_name;
            $b_rename = rename($tmpfname, $temp_file_name_with_extension );
            if (!$b_rename) {throw new LogicException("Cannot rename $tmpfname, $temp_file_name_with_extension ");}
            $file_upload->moveTo($temp_file_name_with_extension);


            //do copy now
            $project = FlowProject::create_project_from_upload(
                                    $temp_file_name_with_extension,$call->args->flow_project_title);
            $success_message = "Copied upload from file $client_file_name to "  . $project->get_project_title();


            $data = [
                'success'=>true,
                'message'=>$success_message,
                'project'=>$project,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        }
        catch (Exception $e) {
            $this->logger->error("Could not copy project: ".$e->getMessage(),['exception'=>$e]);
            $data = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'project'=>$project,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        } finally {
            if ($temp_file_name_with_extension) {unlink($temp_file_name_with_extension);}
        }

    }
}