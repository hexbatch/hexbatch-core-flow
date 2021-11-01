<?php
namespace app\controllers\base;


use app\models\user\FlowUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Views\Twig;


class BasePages
{


    protected Auth $auth;
    protected Logger $logger;
    /**
     * @var Container $container
     */
    protected Container $container;

    protected Twig $view;

    /**
     * @var FlowUser $user
     */
    protected FlowUser $user;

    /**
     * UserLogInPages constructor.
     * @param Auth $auth
     * @param Logger $logger
     * @param Container $container
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function __construct(Auth $auth, Logger $logger, Container $container)
    {
        $this->auth = $auth;
        $this->logger = $logger;
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->user = $this->container->get('user');
    }

    protected function is_ajax_call(ServerRequestInterface $request) : bool {
        $x_header = $request->getHeader('X-Requested-With') ?? [];
        if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
            return  false;
        } else {
            return true;
        }
    }



}