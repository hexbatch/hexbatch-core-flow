<?php
namespace app\controllers\entry;


use app\helpers\AjaxCallData;
use app\hexlet\JsonHelper;

use app\models\entry\FlowEntry;
use app\models\entry\FlowEntrySearchParams;
use app\models\project\FlowProjectUser;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class EntryPages extends EntryBase
{
    const REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY = 'entry_new_form_in_progress_has_error';
    const REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY = 'entry_edit_form_in_progress_has_error';

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @param ?int $page
     * @return ResponseInterface
     * @throws Exception
     *
     */
    public function list_entries(ServerRequestInterface $request, ResponseInterface $response,
                             string $user_name, string $project_name, ?int $page): ResponseInterface
    {
        try {

            $option = new AjaxCallData([
                AjaxCallData::OPTION_LIMIT_SEARCH_TO_PROJECT,
                AjaxCallData::OPTION_ALLOW_EMPTY_BODY,
                AjaxCallData::OPTION_ALLOW_NO_PROJECT_HASH
            ]);

            $option->note = 'list_entries';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_READ;

            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name,
                entry_search_params: new FlowEntrySearchParams(),
                page: $page??1,

            );



            if ($this->is_ajax_call($request)) {
                $data = [
                    'success'=>true,
                    'message'=>'ok',
                    'data'=>$call->entry_array,
                    'page' => $call->entry_search_params_used->getPage(),
                    'page_size' => $call->entry_search_params_used->getPageSize(),
                    'token'=> $call->get_token_with_project_hash($call->project)
                ];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                return $this->view->render($response, 'main.twig', [
                    'page_template_path' => 'entry/list_entries.twig',
                    'page_title' => "Entries for Project $project_name",
                    'page_description' => 'Entry List',
                    'page_number' => $call?->entry_search_params_used->getPage(),
                    'page_size' => $call?->entry_search_params_used->getPageSize(),
                    'project' => $call?->project,
                    'entries' => $call?->entry_array,
                ]);
            }


        } catch (Exception $e) {
            $this->logger->error("Could not render list entries",['exception'=>$e]);
            throw $e;
        }
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
    public function show_entry(ServerRequestInterface $request, ResponseInterface $response,
                                 string $user_name, string $project_name,string $entry_name): ResponseInterface
    {
        try {

            $option = new AjaxCallData([
                AjaxCallData::OPTION_LIMIT_SEARCH_TO_PROJECT,
                AjaxCallData::OPTION_ALLOW_EMPTY_BODY,
                AjaxCallData::OPTION_ALLOW_NO_PROJECT_HASH
            ]);

            $option->note = 'show_entry';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_READ;

            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name,
                entry_name: $entry_name

            );


            $entry_to_show =  $call->entry;
            if (!$entry_to_show) {
                throw new InvalidArgumentException("[show_entry] Could not find entry $entry_name for project $project_name");
            }

            if ($this->is_ajax_call($request)) {
                $data = [
                    'success'=>true,
                    'message'=>'ok',
                    'entry'=>$entry_to_show,
                    'token'=> $call->get_token_with_project_hash($call->project)
                ];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                return $this->view->render($response, 'main.twig', [
                    'page_template_path' => 'entry/show_entry.twig',
                    'page_title' => "Entry ".$entry_to_show->get_title(),
                    'page_description' => "Show Entry " . $entry_to_show->get_title(),
                    'entry'=>$entry_to_show,
                    'project' => $call->project
                ]);
            }


        } catch (Exception $e) {
            $this->logger->error("Could not render show entry",['exception'=>$e]);
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
     * @noinspection PhpUnused
     */
    public function new_entry(ServerRequestInterface $request, ResponseInterface $response,
                                 string $user_name, string $project_name): ResponseInterface
    {
        try {

            $option = new AjaxCallData([
                AjaxCallData::OPTION_LIMIT_SEARCH_TO_PROJECT,
                AjaxCallData::OPTION_ALLOW_EMPTY_BODY,
                AjaxCallData::OPTION_ALLOW_NO_PROJECT_HASH
            ]);

            $option->note = 'new_entry';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;

            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name

            );


            if ($this->is_ajax_call($request)) {
                throw new InvalidArgumentException("Not an ajax action, generates web form");
            }

            if (array_key_exists(static::REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowEntry $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = FlowEntry::create_entry($call->project,null);
                } else {
                    $form_in_progress = FlowEntry::create_entry($call->project,$form_in_progress);
                }
            } else {
                $form_in_progress = FlowEntry::create_entry($call->project,null);
            }

            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'entry/new_entry.twig',
                'page_title' => 'Make A New Entry',
                'page_description' => 'New Entry Form',
                'project' => $call->project,
                'entry' => $form_in_progress

            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render new entry page",['exception'=>$e]);
            throw $e;
        }
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
    public function edit_entry(ServerRequestInterface $request, ResponseInterface $response,
                                 string $user_name, string $project_name,string $entry_name): ResponseInterface
    {
        try {

            $option = new AjaxCallData([
                AjaxCallData::OPTION_LIMIT_SEARCH_TO_PROJECT,
                AjaxCallData::OPTION_ALLOW_NO_PROJECT_HASH,
                AjaxCallData::OPTION_ALLOW_EMPTY_BODY
            ]);

            $option->note = 'edit_entry';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_WRITE;

            $call = $this->get_entry_helper()->validate_ajax_call(
                options: $option,
                request: $request,
                user_name: $user_name,
                project_name: $project_name,
                entry_name: $entry_name

            );


            if ($this->is_ajax_call($request)) {
                throw new InvalidArgumentException("Not an ajax action, generates web form");
            }


            if (array_key_exists(static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowEntry $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $entry_to_edit = $call->entry;
                    if (!$entry_to_edit) {
                        throw new InvalidArgumentException("Could not find entry $entry_name for project $project_name");
                    }
                    $form_in_progress =$entry_to_edit;
                } else {
                    $form_in_progress = FlowEntry::create_entry($call->project,$form_in_progress);
                }
            } else {
                $entry_to_edit = $call->entry;
                if (!$entry_to_edit) {
                    throw new InvalidArgumentException("Could not find entry $entry_name for project $project_name");
                }
                $form_in_progress = $entry_to_edit;
            }

            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'entry/edit_entry.twig',
                'page_title' => "Edit Entry ". $form_in_progress->get_title(),
                'page_description' => "New Entry Form ". $form_in_progress->get_title(),
                'project' => $call->project,
                'entry' => $form_in_progress

            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render edit entry page",['exception'=>$e]);
            throw $e;
        }
    }


}