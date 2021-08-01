<?php
namespace app\controllers\user;

use Delight\Auth\Auth;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Slim\Exception\HttpForbiddenException;
use Psr\Http\Server\RequestHandlerInterface ;



class CheckLoggedInMiddleware
{
    protected  Auth $auth;
    public function __construct (Auth $auth) {
        $this->auth = $auth;
    }
    /**
     *
     * @param  RequestInterface  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     *
     * @return Response
     * @throws HttpForbiddenException if not logged in
     */
    public function __invoke(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->auth->isLoggedIn()) {
            throw new HttpForbiddenException($request, "Need to be logged in first");
        }
        $response = $handler->handle($request);
        $new_response = $response->withAddedHeader('checks-out', time());

        return $new_response;
    }
}
