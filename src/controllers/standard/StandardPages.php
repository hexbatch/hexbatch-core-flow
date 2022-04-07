<?php
namespace app\controllers\standard;

use app\controllers\base\BasePages;
use app\helpers\TagHelper;
use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\models\standard\FlowTagStandardAttribute;
use app\models\tag\FlowTagAttribute;
use app\models\tag\FlowTagCallData;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StandardPages extends BasePages
{
    public function update_tag_standard(ServerRequestInterface $request, ResponseInterface $response,
                                        string                 $user_name, string $project_name,
                                        string                 $tag_guid, string $standard_name) :ResponseInterface {


        try {
            $copy_valid_update = [];
            $option = new FlowTagCallData([
                FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'update_tag_standard';
            $call = TagHelper::get_tag_helper()->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_guid);
            $tag = $call->tag;
            $standard_keys = FlowTagStandardAttribute::getStandardAttributeKeys($standard_name,false);
            $valid_update = [];

            foreach ($call->args as $standard_key_name => $new_standard_key_val) {
                if (in_array($standard_key_name,$standard_keys)) {
                    $valid_update[$standard_key_name] = JsonHelper::to_utf8($new_standard_key_val);
                }
            }

            $copy_valid_update = Utilities::get_utilities()->deep_copy($valid_update);

            foreach ($tag->attributes as $existing_attribute) {
                if (isset($valid_update[$existing_attribute->getTagAttributeName()])) {
                    $existing_attribute->setTagAttributeText($valid_update[$existing_attribute->getTagAttributeName()]);
                    unset($valid_update[$existing_attribute->getTagAttributeName()]);
                }

            }

            foreach ($valid_update as $attribute_key_to_add => $attribute_text_val_to_add) {
                $att = new FlowTagAttribute();
                $att->setTagAttributeName($attribute_key_to_add);
                $att->setTagAttributeText($attribute_text_val_to_add);
                $att->setFlowTagId($tag->flow_tag_id);
                $tag->attributes[] = $att;
            }

            $tag->save(true,true);
            $new_tag = $tag->clone_refresh();
            $data = ['success'=>true,'message'=>'ok','tag'=>$new_tag,'action'=> 'update_tag_standard',
                            'standard_name'=>$standard_name,'standard_data'=>$copy_valid_update,'token'=> $call->new_token];

            $payload = JsonHelper::toString($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch(Exception $e) {
            $this->logger->error("Could not update tag standard: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),
                'action'=> 'update_tag_standard',
                'standard_name'=>$standard_name,'standard_data'=>$copy_valid_update,'token'=> $call->new_token?? null];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }
}