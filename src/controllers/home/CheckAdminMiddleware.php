<?php
namespace app\controllers\home;

use app\helpers\AdminHelper;
use app\models\base\FlowBase;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Response;


class CheckAdminMiddleware extends FlowBase
{

    /**
     *
     * @param RequestInterface $request PSR-7 request
     * @param RequestHandlerInterface $handler PSR-15 request handler
     *
     *
     * @return Response
     * @throws HttpForbiddenException if not logged in
     * @throws Exception
     */
    public function __invoke(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $helper = AdminHelper::getInstance(static::$container);
        if (!$helper->get_current_user()->getFlowUserId()) {
            throw new HttpForbiddenException($request, "Need to be logged in as Administrator");
        }

        if (!$helper->is_current_user_admin()) {
            throw new HttpForbiddenException($request, "Need to be Administrator");
        }
        $response = $handler->handle($request);
        $new_response = $response->withAddedHeader('checks-out', time());

        return $new_response;
    }
}
