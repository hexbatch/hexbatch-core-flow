<?php
namespace app\user;

use app\exceptions\HexletErrorToUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Views\Twig;


class HomePages
{

    protected Auth $auth;
    protected Logger $logger;
    /**
     * @var Container $container
     */
    protected Container $container;

    protected Twig $view;

    protected object $user;

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

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function home(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_name' => 'home.twig',
                'page_title' => 'Home',
                'page_description' => 'No Place Like Home',
                'user' => $this->user
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
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
}