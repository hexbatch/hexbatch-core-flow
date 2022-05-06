<?php
namespace app\controllers\entry;

use app\helpers\AjaxCallData;
use app\hexlet\JsonHelper;
use app\models\entry\FlowEntry;
use app\models\project\FlowProjectUser;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EntryLifeTime extends EntryBase {
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function create_entry(ServerRequestInterface $request, ResponseInterface $response,
                                 string $user_name, string $project_name): ResponseInterface
    {
        $call = null;
        try {

            $option = new AjaxCallData([
                AjaxCallData::OPTION_ENFORCE_AJAX,
                AjaxCallData::OPTION_MAKE_NEW_TOKEN,
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'create_entry';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;
            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name
            );


            $entry_to_insert =  FlowEntry::create_entry($call->project,$call->args);
            $entry_to_insert->save_entry(true);
            $_SESSION[EntryPages::REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY] = null;

            $call->entry = $entry_to_insert->clone_with_missing_data($call->project);


            $data = [
                'success'=>true,
                'message'=>'Inserted Entry',
                'entry'=>$call->entry,
                'entry_url' => EntryRoutes::get_entry_url(EntryRoutes::SHOW,$request,$call),
                'project' => $call->project,
                'token'=> $call->get_token_with_project_hash($call->project)

            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);


        } catch (Exception $e) {
            $this->logger->error("Could not insert entry",['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),
                    'entry'=>$call?->entry,
                    'entry_url' => EntryRoutes::get_entry_url(EntryRoutes::SHOW,$request,$call),
                    'project' => $call?->project,
                    'token'=> $call?->get_token_with_project_hash($call?->project)
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);


        } //end catch
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @param string $entry_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function update_entry(ServerRequestInterface $request, ResponseInterface $response,
                                 string $user_name, string $project_name,string $entry_name): ResponseInterface
    {
        $call = null;
        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_ENFORCE_AJAX,
                AjaxCallData::OPTION_MAKE_NEW_TOKEN,
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'update_entry';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;
            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name,
                entry_name: $entry_name
            );
            $entry_to_update =  $call->entry;
            if (!$entry_to_update) {
                throw new InvalidArgumentException("[update_entry] Could not find entry $entry_name for project $project_name");
            }

            $entry_to_save = $entry_to_update->clone_with_missing_data($call->project);
            $entry_to_save->save(true);
            $_SESSION[EntryPages::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY] = null;

            $returned_entry = $entry_to_save->clone_with_missing_data($call->project);


            $data = [
                'success'=>true,
                'message'=>'Updated Entry',
                'entry'=>$returned_entry,
                'entry_url' =>EntryRoutes::get_entry_url(EntryRoutes::SHOW,$request,$call),
                'project' => $call?->project,
                'token'=> $call->get_token_with_project_hash($call?->project)

            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);


        } catch (Exception $e) {
            $this->logger->error("Could not update entry",['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),
                    'entry'=>$call?->entry,
                    'entry_url'=>null,
                    'project' => $call?->project,
                    'token'=> $call?->get_token_with_project_hash($call?->project) ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);


        } //end catch
    } //end function


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @param string $entry_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function delete_entry(ServerRequestInterface $request, ResponseInterface $response,
                                 string $user_name, string $project_name,string $entry_name): ResponseInterface
    {
        $call = null;
        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_ENFORCE_AJAX,
                AjaxCallData::OPTION_MAKE_NEW_TOKEN,
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'delete_entry';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;
            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name,
                entry_name: $entry_name
            );

            if (!$call->entry) {
                throw new InvalidArgumentException("[delete_entry] Could not find entry $entry_name for project $project_name");
            }

            $call->entry->delete_entry();

            $data = [
                'success'=>true,
                'message'=>'ok',
                'entry'=>$call->entry,
                'token'=> $call->get_token_with_project_hash($call?->entry->get_project())
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);


        } catch (Exception $e) {
            $data = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'entry'=>null,
                'token'=> $call->get_token_with_project_hash(null)
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }



}