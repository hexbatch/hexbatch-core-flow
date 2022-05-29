<?php
namespace app\controllers\home;

use app\controllers\base\BasePages;
use app\helpers\AjaxCallData;
use app\helpers\LuaHelper;
use app\hexlet\JsonHelper;
use Exception;
use InvalidArgumentException;
use LuaSandbox;
use LuaSandboxError;
use LuaSandboxRuntimeError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class LuaPages extends BasePages
{

    protected function getHelper() : LuaHelper { return LuaHelper::get_lua_helper();}

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws
     */
    public function test_lua_no_project( ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {

        $call = null;
        $lua_return = null;


        try {

            $option = new AjaxCallData([
                AjaxCallData::OPTION_ALLOW_NO_PROJECT_HASH,
                 AjaxCallData::OPTION_ALLOW_EMPTY_BODY]);

            $option->note = 'test_lua_no_project';

            $call = $this->getHelper()->validate_ajax_call($option,$request);
            if (!$call->args->lua_code??null || empty(trim($call->args->lua_code))) {
                throw new InvalidArgumentException("Missing lua_code info");
            }

            $lua_code = trim($call->args->lua_code);

            $sandbox = new LuaSandbox;
            $sandbox->setMemoryLimit(50 * 1024 * 1024);
            $sandbox->setCPULimit(10);
            $this->getHelper()->register_standard_libraries($sandbox);

            $sandbox->loadString($lua_code)->call([]);

            $data = ['success'=>true,'message'=>'','result'=>$lua_return,
                        'code'=>0,'token'=> $call->get_token_with_project_hash($call?->project)];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        }
        catch (LuaSandboxError $e) {

            $b_success = false;

            $response_code = 500;
            $code = $e->getCode();
            if ($e instanceof LuaSandboxRuntimeError) {
                $response_code = 200;
                $b_success = true;
                $code = 100;
            }

            $this->logger->warning("[test_lua_no_project] Lua issue [$response_code]",['exception'=>$e]);

            $data = [
                'success'=>$b_success,
                'message'=>$e->getMessage(),
                'code'=>$code,
                'result'=>$lua_return,
                'token'=> $call->get_token_with_project_hash($call?->project)
            ];

            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($response_code);
        }

        catch (Exception $e) {
            $this->logger->error("[test_lua_no_project] error",['exception'=>$e]);
            throw $e;
        }
    }




}