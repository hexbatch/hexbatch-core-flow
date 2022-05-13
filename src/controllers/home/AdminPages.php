<?php
namespace app\controllers\home;

use app\controllers\base\BasePages;
use app\helpers\AdminHelper;
use app\helpers\SQLHelper;
use app\hexlet\JsonHelper;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class AdminPages extends BasePages
{

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws
     * @noinspection PhpUnused
     */
    public function test( ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {


        $info = AdminHelper::getInstance()->admin_test($request);
        $response->getBody()->write(JsonHelper::toString($info));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }



    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function php_info( ResponseInterface $response) :ResponseInterface {
        ob_start();
        phpinfo();
        $info = ob_get_clean();
        $response->getBody()->write($info);
        return $response;
    }


    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @noinspection PhpUnused
     * @throws Exception
     */
    public function admin_boot( ResponseInterface $response) :ResponseInterface
    {
        $id = AdminHelper::getInstance()->maybe_add_admin_project();
        $payload = JsonHelper::toString(['id'=>$id]);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @noinspection PhpUnused
     * @throws Exception
     */
    public function redo_triggers( ResponseInterface $response) :ResponseInterface {
        $data = [];
        $data['triggers'] = SQLHelper::redo_all_triggers();
        $payload = JsonHelper::toString($data);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @noinspection PhpUnused
     * @throws Exception
     */
    public function thing_management( ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {

        $args = $request->getQueryParams();
        $data = [];


        if (isset($args['truncate']) && JsonHelper::var_to_boolean($args['truncate'])) {
            $data['truncate'] = SQLHelper::truncate_flow_things();
        }

        if (isset($args['refresh']) && JsonHelper::var_to_boolean($args['refresh'])) {
            $data['refresh'] = SQLHelper::refresh_flow_things_except_css();
        }

        if (isset($args['css']) && JsonHelper::var_to_boolean($args['css'])) {
            $data['css'] = SQLHelper::refresh_flow_things();
        }



        $payload = JsonHelper::toString($data);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}