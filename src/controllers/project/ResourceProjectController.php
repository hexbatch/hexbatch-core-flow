<?php
namespace app\controllers\project;

use app\controllers\user\UserPages;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\models\project\FlowProjectFiles;
use app\models\project\FlowProjectUser;
use Exception;
use finfo;
use InvalidArgumentException;
use ParagonIE\AntiCSRF\AntiCSRF;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Stream;
use Slim\Routing\RouteContext;

/**
 * @since 0.5.2
 * Manages access and control to project resources
 */
class ResourceProjectController extends BaseProjectController {


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     *
     */
    public function upload_resource_file(ServerRequestInterface $request,ResponseInterface $response,
                                         string $user_name, string $project_name) :ResponseInterface {

        $file_name = null;
        $new_url = null;
        $new_token = null;
        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            $args = $request->getParsedBody();
            $b_rootless_auth = isset($args['b_use_rootless_auth']) && JsonHelper::var_to_boolean($args['b_use_rootless_auth']);

            if ($b_rootless_auth) {
                $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
                $new_token = $csrf->getTokenArray();
            } else {
                $csrf = new FlowAntiCSRF($args);
            }
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
            }




            try {

                $file_upload = $this->get_project_helper()->find_and_move_uploaded_file($request,'flow_resource_file');
                $resource_base = $project->getFlowProjectFiles()->get_resource_directory();

                $file_resource_path = $resource_base. DIRECTORY_SEPARATOR. $file_upload->getClientFilename();
                $file_name = $file_upload->getClientFilename();

                $file_upload->moveTo($file_resource_path);

                $project->commit_changes("Added resource file $file_name\n\nFile path is $file_resource_path");

                $new_urls = $project->getFlowProjectFiles()->get_resource_urls([$file_resource_path]);
                $new_url = $new_urls[0];
                $data = ['success'=>true,'message'=>'ok file upload','file_name'=>$file_name,'action' => 'upload_resource_file',
                    'new_file_path'=>$file_resource_path,'new_file_url' => $new_urls[0]];

                $data['token'] = $new_token;
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } catch (Exception $e) {
                $this->logger->error("Cannot upload file", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                $data = ['success'=>false,'message'=>($file_name??'') .': '.$e->getMessage(),
                    'file_name'=>($file_name??''),'action' => 'upload_resource_file',
                    'new_file_path'=>($file_resource_path??''),'new_file_url' => $new_url

                ];
                $data['token'] = $new_token;
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to project resources after error", ['exception' => $e]);
                throw $e;
            }
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
    public function delete_resource_file(ServerRequestInterface $request,ResponseInterface $response,
                                         string $user_name, string $project_name) :ResponseInterface {

        $token = null;
        try {
            $args = $request->getParsedBody();
            if (empty($args)) {
                throw new InvalidArgumentException("No data sent");
            }
            $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
            }

            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }

            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);
            $token_lock_to = '';
            $token = $csrf->getTokenArray($token_lock_to);


            $file_url = $args['file_url'] ?? '';
            if (!$file_url) {throw new InvalidArgumentException("Need a file url");}

            $file_url_parts = array_reverse(explode('/',$file_url));
            $backwards_parts = [];
            foreach ($file_url_parts as $part) {
                if (strpos($part,":") !== false) {break;}
                $backwards_parts[] =$part;
                if ($part === 'resources') {break;}
            }
            $file_part_path = implode(DIRECTORY_SEPARATOR,array_reverse($backwards_parts)) ;
            $base_resource_file_path = $project->getFlowProjectFiles()->get_project_directory(); //no slash at end
            $test_file_path = $base_resource_file_path . DIRECTORY_SEPARATOR . $file_part_path;
            $real_file_path = realpath($test_file_path);
            if (!$real_file_path) {
                throw new RuntimeException("Could not find the file of $file_part_path in the project repo");
            }
            if (!is_writable($real_file_path)){
                throw new RuntimeException("no system permissions to delete the file of $file_part_path in the project repo");
            }
            unlink($real_file_path);
            $project->commit_changes("Deleted the resource $file_part_path");

            $data = ['success'=>true,'message'=>'deleted file ','file_url'=>$file_url,'token'=> $token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $token];
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
    public function import_from_file(ServerRequestInterface $request,ResponseInterface $response,
                                     string $user_name, string $project_name) :ResponseInterface {

        try {
            $csrf = new AntiCSRF;

            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
                }
            }


            $file_upload = $this->get_project_helper()->find_and_move_uploaded_file($request,'repo_or_patch_file');
            $file_name = $file_upload->getClientFilename();
            $success_message = "Nothing Done "  . $project->flow_project_title;
            $args = $request->getParsedBody();
            if (array_key_exists('import-now',$args)) {
                //do push now
                $push_status = $project->update_repo_from_file($file_upload);
                $success_message = "Merging from file $file_name to "  . $project->flow_project_title . "<br>$push_status";
            }

            try {
                UserPages::add_flash_message('success',$success_message );
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_history',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to all projects after successful merge from file", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot upload file file: <b>" . $e->getMessage().'</b>');
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_resources',[
                    "user_name" => $user_name ,
                    "project_name" => $project_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to project import settings after error", ['exception' => $e]);
                throw $e;
            }
        }

    }

    /**
     * Permission protected project files
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @param string $resource
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function get_resource_file(ServerRequestInterface $request,ResponseInterface $response,
                                      string $user_name, string $project_name,string $resource) :ResponseInterface {

        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_READ);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }


            $resource_path = $project->getFlowProjectFiles()->get_project_directory(). DIRECTORY_SEPARATOR .
                FlowProjectFiles::REPO_FILES_DIRECTORY . DIRECTORY_SEPARATOR . $resource ;
            if (!is_readable($resource_path)) {
                throw new HttpNotFoundException($request,"Resource $resource NOT found in the resources directory of $project_name");
            }

            $file_size = filesize($resource_path);
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $fi->file($resource_path);


            $response = $response
                ->withHeader('Content-Type', $mime_type)
                ->withHeader('Content-Length', $file_size)
                ->withHeader('Content-Disposition', "attachment; filename=$resource_path")
                ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->withHeader('Cache-Control', 'post-check=0, pre-check=0')
                ->withHeader('Pragma', 'no-cache')
                ->withBody((new Stream(fopen($resource_path, 'rb'))));

            return $response;

        } catch (Exception $e) {
            $this->logger->error("Could not download project resource",['exception'=>$e]);
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
    public function resources( ServerRequestInterface $request,ResponseInterface $response,
                               string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            $resource_urls = $project->getFlowProjectFiles()->get_resource_urls();


            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/resources.twig',
                'page_title' => "Project Resources for $project->flow_project_title",
                'page_description' => 'View and upload publicly viewable resources for this project',
                'project' => $project,
                'resource_urls' => $resource_urls
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render resources page",['exception'=>$e]);
            throw $e;
        }
    }
}