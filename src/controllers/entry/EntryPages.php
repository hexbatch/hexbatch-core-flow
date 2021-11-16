<?php
namespace app\controllers\entry;


use app\controllers\user\UserPages;
use app\hexlet\JsonHelper;

use app\models\entry\FlowEntry;
use app\models\entry\FlowEntrySearchParams;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;


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
     * @noinspection PhpUnused
     */
    public function list_entries(ServerRequestInterface $request, ResponseInterface $response,
                             string $user_name, string $project_name, ?int $page): ResponseInterface
    {
        try {
            $options = new FlowEntryCallData();
            $options->note = "list_entries";
            $options->set_option(FlowEntryCallData::OPTION_LIMIT_SEARCH_TO_PROJECT);
            $search_params = new FlowEntrySearchParams();
            $search_params->set_page($page??1);
            $call = $this->validate_call($options,$request,null,$user_name,$project_name,
                null,'read',$search_params,$page);

            if ($call->is_ajax_call) {
                $data = [
                    'success'=>true,
                    'message'=>'ok',
                    'data'=>$call->entry_array,
                    'page' => $call->search_used->get_page(),
                    'page_size' => $call->search_used->get_page_size(),
                    'token'=> $call->new_token
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
                    'page_number' => $call->search_used->get_page(),
                    'page_size' => $call->search_used->get_page_size(),
                    'project' => $call->project,
                    'entries' => $call->entry_array,
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
            $options = new FlowEntryCallData();
            $options->note = "show_entry";
            $call = $this->validate_call($options,$request,null,$user_name,$project_name, $entry_name);
            $entry_to_show =  $call->entry_array[0]??null;
            if (!$entry_to_show) {
                throw new InvalidArgumentException("[show_entry] Could not find entry $entry_name for project $project_name");
            }

            if ($call->is_ajax_call) {
                $data = [
                    'success'=>true,
                    'message'=>'ok',
                    'entry'=>$entry_to_show,
                    'token'=> $call->new_token
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

            $options = new FlowEntryCallData();
            $options->note = "new_entry";
            $call = $this->validate_call($options,$request,null,$user_name,$project_name);
            if ($call->is_ajax_call) {
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

            $options = new FlowEntryCallData();
            $options->note = "edit_entry";
            $call = $this->validate_call($options,$request,null,$user_name,$project_name,$entry_name);
            $entry_to_edit = $call->entry_array[0]??null;
            if (!$entry_to_edit) {
                throw new InvalidArgumentException("Could not find entry $entry_name for project $project_name");
            }
            if ($call->is_ajax_call) {
                throw new InvalidArgumentException("Not an ajax action, generates web form");
            }

            if (array_key_exists(static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowEntry $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress =$entry_to_edit;
                } else {
                    $form_in_progress = FlowEntry::create_entry($call->project,$form_in_progress);
                }
            } else {
                $form_in_progress = $entry_to_edit;
            }

            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/edit_entry.twig',
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
        $entry_to_insert = null;
        $call = null;
        try {
            $options = new FlowEntryCallData();
            $options->note = "create_entry";
            $options->set_option(FlowEntryCallData::OPTION_VALIDATE_TOKEN);
            $options->set_option(FlowEntryCallData::OPTION_NO_CHILDREN_IN_SEARCH);
            if ($this->is_ajax_call($request)) {
                $options->set_option(FlowEntryCallData::OPTION_MAKE_NEW_TOKEN);
            }
            $call = $this->validate_call($options,$request,null,$user_name,$project_name);
            $entry_to_insert =  FlowEntry::create_entry($call->project,$call->args);
            $entry_to_insert->save_entry(true,false);
            $_SESSION[static::REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY] = null;

            if ($call->is_ajax_call) {
                $returned_entry = $entry_to_insert->clone_with_missing_data($call->project);
                $data = [
                    'success'=>true,
                    'message'=>'Inserted Entry',
                    'entry'=>$returned_entry,
                    'token'=> $call->new_token
                ];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                UserPages::add_flash_message(
                    'success',
                    "Updated Entry  " . $entry_to_insert->get_title()
                );

                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('show_entry',[
                    "user_name" => $call->project->get_admin_user()->flow_user_name,
                    "project_name" => $call->project->flow_project_title,
                    "entry_name" => $entry_to_insert->get_title()
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            }


        } catch (Exception $e) {
            $this->logger->error("Could not insert entry",['exception'=>$e]);
            if ($this->is_ajax_call($request)) {
                $token = null;
                if ($call) { $token = $call->new_token?? null;}
                $data = ['success'=>false,'message'=>$e->getMessage(),'entry'=>null,'token'=> $token];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            } else {
                UserPages::add_flash_message('warning', "Cannot insert entry " . $e->getMessage());
                $_SESSION[static::REM_NEW_ENTRY_WITH_ERROR_SESSION_KEY] = $entry_to_insert;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('create_entry',[
                    "user_name" => $call->project->get_admin_user()->flow_user_name,
                    "project_name" => $call->project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            }


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
        $entry_to_save = null;
        $call = null;
        try {
            $options = new FlowEntryCallData();
            $options->note = "update_entry";
            $options->set_option(FlowEntryCallData::OPTION_VALIDATE_TOKEN);
            $options->set_option(FlowEntryCallData::OPTION_NO_CHILDREN_IN_SEARCH);
            if ($this->is_ajax_call($request)) {
                $options->set_option(FlowEntryCallData::OPTION_MAKE_NEW_TOKEN);
            }
            $call = $this->validate_call($options,$request,null,$user_name,$project_name, $entry_name);
            $entry_to_update =  $call->entry_array[0]??null;
            if (!$entry_to_update) {
                throw new InvalidArgumentException("[update_entry] Could not find entry $entry_name for project $project_name");
            }

            $entry_to_save = $entry_to_update->clone_with_missing_data($call->project);
            $entry_to_save->save(true,false);
            $_SESSION[static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY] = null;

            if ($call->is_ajax_call) {
                $returned_entry = $entry_to_save->clone_with_missing_data($call->project);
                $data = [
                    'success'=>true,
                    'message'=>'Updated Entry',
                    'entry'=>$returned_entry,
                    'token'=> $call->new_token
                ];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                UserPages::add_flash_message(
                    'success',
                    "Updated Entry  " . $entry_to_update->flow_project_title
                );

                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('show_entry',[
                    "user_name" => $call->project->get_admin_user()->flow_user_name,
                    "project_name" => $call->project->flow_project_title,
                    "entry_name" => $entry_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            }


        } catch (Exception $e) {
            $this->logger->error("Could not update entry",['exception'=>$e]);
            if ($this->is_ajax_call($request)) {
                $token = null;
                if ($call) { $token = $call->new_token?? null;}
                $data = ['success'=>false,'message'=>$e->getMessage(),'entry'=>null,'token'=> $token];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            } else {
                UserPages::add_flash_message('warning', "Cannot update entry " . $e->getMessage());
                $_SESSION[static::REM_EDIT_ENTRY_WITH_ERROR_SESSION_KEY] = $entry_to_save;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('edit_entry',[
                    "user_name" => $call->project->get_admin_user()->flow_user_name,
                    "project_name" => $call->project->flow_project_title,
                    "entry_name" => $entry_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            }


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
            $options = new FlowEntryCallData();
            $options->note = "delete_entry";
            $options->set_option(FlowEntryCallData::OPTION_VALIDATE_TOKEN);
            if ($this->is_ajax_call($request)) {
                $options->set_option(FlowEntryCallData::OPTION_MAKE_NEW_TOKEN);
            }
            $call = $this->validate_call($options,$request,null,$user_name,$project_name, $entry_name);
            $entry_to_delete =  $call->entry_array[0]??null;
            if (!$entry_to_delete) {
                throw new InvalidArgumentException("[delete_entry] Could not find entry $entry_name for project $project_name");
            }

            $entry_to_delete->delete_entry();

            if ($call->is_ajax_call) {
                $data = [
                    'success'=>true,
                    'message'=>'ok',
                    'entry'=>$entry_to_delete,
                    'token'=> $call->new_token
                ];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                UserPages::add_flash_message(
                    'success',
                    "Deleted Entry  " . $entry_to_delete->flow_project_title
                );

                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('show_entry',[
                    "user_name" => $call->project->get_admin_user()->flow_user_name,
                    "project_name" => $call->project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            }


        } catch (Exception $e) {
            if ($this->is_ajax_call($request)) {
                $token = null;
                if ($call) { $token = $call->new_token?? null;}
                $data = ['success'=>false,'message'=>$e->getMessage(),'entry'=>null,'token'=> $token];
                $payload = JsonHelper::toString($data);

                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
            $this->logger->error("Could not render show entry",['exception'=>$e]);
            throw $e;
        }
    }



}