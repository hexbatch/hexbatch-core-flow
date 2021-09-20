<?php
namespace app\controllers\base;

use app\controllers\user\UserPages;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\GoodZipArchive;
use app\models\project\FlowGitFile;
use app\models\project\FlowProject;
use app\models\project\FlowProjectUser;
use app\models\user\FlowUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use finfo;
use InvalidArgumentException;
use LogicException;
use Monolog\Logger;
use ParagonIE\AntiCSRF\AntiCSRF;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Stream;
use Slim\Routing\RouteContext;
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



}