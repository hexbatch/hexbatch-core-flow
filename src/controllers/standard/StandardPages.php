<?php
namespace app\controllers\standard;

use app\controllers\base\BasePages;
use app\helpers\AjaxCallData;
use app\helpers\TagHelper;
use app\hexlet\JsonHelper;
use app\models\standard\FlowTagStandardAttribute;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StandardPages extends BasePages
{

    public function delete_tag_standard(ServerRequestInterface $request, ResponseInterface $response,
                                        string                 $user_name, string $project_name,
                                        string                 $tag_guid, string $standard_name) :ResponseInterface {

        $call = null;
        try {
            $copy_valid_update = [];
            $option = new AjaxCallData([
                AjaxCallData::OPTION_ENFORCE_AJAX,AjaxCallData::OPTION_MAKE_NEW_TOKEN,AjaxCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_tag_standard';
            $call = TagHelper::get_tag_helper()->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_guid);
            $tag = $call->tag;
            $da_standy_keys = FlowTagStandardAttribute::getStandardAttributeKeys($standard_name,false);
            $valid_update = [];


            foreach ($da_standy_keys as $some_key) {
                $valid_update[$some_key] = true;
            }

            $db = $this->get_connection();

            try {
                if(!$db->inTransaction()){$db->beginTransaction();}
                $deleted_attributes = [];
                foreach ($tag->attributes as $existing_attribute) {
                    if (isset($valid_update[$existing_attribute->getTagAttributeName()])) {
                        $deleted_attributes[] = $tag->delete_attribute_by_name($existing_attribute->getTagAttributeName());
                    }
                }
                $tag->save(false,true);
                if ($db->inTransaction()) {$db->commit();}
            } catch (Exception $e) {
                if ($db->inTransaction()) {$db->rollBack();}
                throw $e;
            }


            $new_tag = $tag->clone_refresh();
            $call->project->save();
            $data = [
                'success'=>true,'message'=>'ok','tag'=>$new_tag,'action'=> 'delete_tag_standard',
                'standard_name'=>$standard_name,
                'standard_data'=>$valid_update,
                'removed_attributes' => $deleted_attributes,
                'token'=> $call->get_token_with_project_hash($call->project)
            ];

            $payload = JsonHelper::toString($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch(Exception $e) {
            $this->logger->error("Could not delete tag standard: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),
                'action'=> 'update_tag_standard',
                'standard_name'=>$standard_name,
                'standard_data'=>$copy_valid_update,
                'removed_attributes' => [],
                'token'=> $call?->get_token_with_project_hash($call->project)
                ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }




    public function update_tag_standard(ServerRequestInterface $request, ResponseInterface $response,
                                        string                 $user_name, string $project_name,
                                        string                 $tag_guid, string $standard_name) :ResponseInterface {

        $call = null;
        try {
            $valid_update = [];
            $option = new AjaxCallData([
                AjaxCallData::OPTION_ENFORCE_AJAX,AjaxCallData::OPTION_MAKE_NEW_TOKEN,AjaxCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'update_tag_standard';
            $call = TagHelper::get_tag_helper()->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_guid);
            $tag = $call->tag;
            $standard_keys = FlowTagStandardAttribute::getStandardAttributeKeys($standard_name,false);


            foreach ($call->args as $standard_key_name => $new_standard_key_val) {
                if (in_array($standard_key_name,$standard_keys)) {
                    $valid_update[$standard_key_name] = JsonHelper::to_utf8($new_standard_key_val);
                }
            }

            $tag->set_standard_by_raw($standard_name,$call->args);

            $tag->save(true,true);
            $new_tag = $tag->clone_refresh();
            $call->project->save();
            $data = ['success'=>true,'message'=>'ok','tag'=>$new_tag,'action'=> 'update_tag_standard',
                            'standard_name'=>$standard_name,'standard_data'=>$valid_update,
                        'token'=> $call->get_token_with_project_hash($call->project)
            ];

            $payload = JsonHelper::toString($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch(Exception $e) {
            $this->logger->error("Could not update tag standard: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),
                'action'=> 'update_tag_standard',
                'standard_name'=>$standard_name,'standard_data'=>$valid_update,
                'token'=> $call->get_token_with_project_hash($call->project)
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }
}