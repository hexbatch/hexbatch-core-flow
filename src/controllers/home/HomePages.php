<?php
namespace app\controllers\home;

use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function root( ResponseInterface $response) :ResponseInterface {
        try {
            exec('cd /var/www/flow_projects/ && cat times.txt  2>&1',$output,$result_code);
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'root.twig',
                'page_title' => 'Root',
                'page_description' => 'No Place Like Home',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render root page",['exception'=>$e]);
            throw $e;
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

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function general_search( ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {

        $args = $request->getQueryParams();


        $search = new GeneralSearchParams();

        $root = $args;
        if (isset($args['search'])) { $root = $args['search'];}

        if (isset($root['term'])) {
            $search->title = trim($root['term']);
        }

        if (isset($root['guid'])) {
            $search->guid = trim($root['guid']);
        }

        if (isset($root['title'])) {
            $search->title = trim($root['title']);
        }

        if (isset($root['created_at_ts']) && intval($root['created_at_ts'])) {
            $search->created_at_ts = (int)($root['created_at_ts']);
        }



        $page = 1;
        if (isset($args['page'])) {
            $page_number = intval($args['page']);
            if ($page_number > 0) {
                $page = $page_number;
            }
        }


        $matches = GeneralSearch::general_search($search,$page);
        $b_more = true;
        if (count($matches) < GeneralSearch::DEFAULT_PAGE_SIZE) {
            $b_more = false;
        }

        $data = [
            "results" => $matches,
            "pagination" => [
                "more" => $b_more,
                "page" => $page
            ]
        ];

        $payload = json_encode($data);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}