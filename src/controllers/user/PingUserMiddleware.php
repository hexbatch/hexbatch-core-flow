<?php
namespace app\controllers\user;

use app\models\user\FlowUser;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Http\Server\RequestHandlerInterface ;



class PingUserMiddleware
{
    /**
     * @var FlowUser $user
     */
    protected FlowUser $user;
    public function __construct (FlowUser $auth) {
        $this->user = $auth;
    }
    /**
     *
     * @param  RequestInterface  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     *
     * @return Response
     */
    public function __invoke(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->user->flow_user_id) {
            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                $this->user->ping();
            }
            $_SESSION[FlowUser::SESSION_USER_KEY] = $this->user;
        }
        $response = $handler->handle($request); //set above this to handle before page logic


        return $response;
    }
}
