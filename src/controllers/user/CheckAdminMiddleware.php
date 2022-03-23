<?php
namespace app\controllers\user;

use app\helpers\AdminHelper;
use app\helpers\ProjectHelper;
use app\models\base\FlowBase;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Slim\Exception\HttpForbiddenException;
use Psr\Http\Server\RequestHandlerInterface ;



class CheckAdminMiddleware extends FlowBase
{
    /**
     *
     * @param RequestInterface $request PSR-7 request
     * @param RequestHandler $handler PSR-15 request handler
     *
     *
     * @return Response
     * @throws HttpForbiddenException if not logged in
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $helper = AdminHelper::getInstance(static::$container);
        if (!$helper->get_current_user()->flow_user_id) {
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
