<?php
namespace app\controllers\user;

use app\models\project\FlowProject;
use app\models\user\FlowUser;
use Delight\Auth\Auth;
use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;



class UserPages {

    protected  Auth $auth;
    protected  Logger $logger;
    /**
     * @var Container $container
     */
    protected  Container $container;

    protected Twig $view;

    protected object $user;

    protected static string $flash_key_in_session;

    public static function set_flash_key_in_session($key) {
        static::$flash_key_in_session = $key;
    }


    public static function add_flash_message($type,$message) {
        $flash_key = static::$flash_key_in_session ;
        if (!array_key_exists($flash_key,$_SESSION)) {
            $_SESSION[$flash_key] = [];
        }
        $node = (object)[
          'type' => $type,
          'message' => $message,
        ];
        $_SESSION[$flash_key][] = $node;
    }

    /**
     * UserLogInPages constructor.
     * @param Auth $auth
     * @param Logger $logger
     * @param Container $container
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function __construct (Auth $auth,Logger $logger, Container $container) {
        $this->auth = $auth;
        $this->logger = $logger;
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->user = $this->container->get('user');
    }




    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function login_form(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/login.twig',
                'page_title' => 'Login',
                'page_description' => 'Login Here',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HttpInternalServerErrorException($request,$e->getMessage());
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function do_logout(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface {

        try {
            $this->auth->logOut();
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('root');
            $response = $response->withStatus(302);

            return $response->withHeader('Location', $url);
        } catch (Exception $e) {
            $this->logger->error("Could not logout",['exception'=>$e]);
            throw new HttpInternalServerErrorException($request,$e->getMessage());
        }
    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function register_form(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig',  [
                'page_template_path' => 'user/register.twig',
                'page_title' => 'Register',
                'page_description' => 'Please Sign UP',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HttpInternalServerErrorException($request,$e->getMessage());
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function submit_login(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface  {


        $args = $request->getParsedBody();
        $email_or_username = $args['hexbatch_email_or_username'];
        $password = $args['hexbatch_password'];
        $remember_me = 60 * 60;
        if (isset($args['hexbatch_remember_me'])) {
            $remember_me = $remember_me * 24 * 7;
        }

        try {
            if(filter_var($email_or_username, FILTER_VALIDATE_EMAIL)) {
                // valid address
                $this->auth->login($email_or_username, $password, $remember_me);
            }
            else {
                // invalid address
                $this->auth->loginWithUsername($email_or_username, $password, $remember_me);
            }

            try {
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('user_home');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to user home after successful log in",['exception'=>$e]);
                throw $e;
            }

        } catch (InvalidEmailException $e) {
            $message = ('Wrong email address');
        } catch (InvalidPasswordException $e) {
            $message = ('Wrong password');
        } catch (EmailNotVerifiedException $e) {
            $message = ('Email not verified');
        } catch (TooManyRequestsException $e) {
            $message = ('Too many requests');
        } catch (Exception $e) {
            $this->logger->error('some sort of error in user login',['exception'=>$e]);
            throw $e;
        }

        try {
            static::add_flash_message('warning',$message);
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $url = $routeParser->urlFor('login');
            $response = $response->withStatus(302);
            return $response->withHeader('Location', $url);
        } catch (Exception $e) {
            $this->logger->error("Could not render logged in page",['exception'=>$e]);
            throw $e;
        }


    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function submit_registration(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface {
        $args = $request->getParsedBody();
        $user_name = $args['hexbatch_username'];
        $email = $args['hexbatch_email'];
        $password = $args['hexbatch_password'];
        $confirm_password = $args['hexbatch_confirm_password'];


        $b_name_ok = false;
        if (preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $user_name) === 0) {
            if (mb_strlen($user_name) > 3 && mb_strlen($user_name) < 39) {
                $b_name_ok = true;
            }
        }


        $b_password_ok = false;
        if (preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $password) === 0) {
            if ($confirm_password === $password) {
                $b_password_ok = true;
            }
        }


        try {

            if (!$b_name_ok) {
                throw new InvalidArgumentException('User name cannot have invisible chars and be between 4 and 29 characters');
            }

            if (!$b_password_ok) {
                throw new InvalidArgumentException('Password cannot have invisible characters;  and be between 4 and 29 characters ; and match the confirmation password');
            }

            $b_match = FlowProject::check_valid_title($user_name);
            if (!$b_match) {
                throw new InvalidArgumentException("User Name needs to be all alpha numeric or dash only. First character cannot be a number");
            }


            $auth = $this->auth;
            $userId = $auth->register($email, $password, $user_name, function ($selector, $token)  use($auth)  {

                $error_message = '';
                try {
                    $this->auth->confirmEmail($selector, $token);

                } catch (InvalidSelectorTokenPairException $e) {
                    $error_message = ('Invalid token');
                } catch (TokenExpiredException $e) {
                    $error_message = ('Token expired');
                } catch (UserAlreadyExistsException $e) {
                    $error_message = ('Email address already exists');
                } catch (TooManyRequestsException $e) {
                    $error_message = ('Too many requests');
                } catch (Exception $e) {
                    $error_message = $e->getMessage();
                }
                if ($error_message) {
                    throw new InvalidArgumentException($error_message);
                }

            });

            if (empty($error_message)) {
                $b_found = FlowUser::get_base_details($userId, $base_email, $base_username);
                if (!$b_found) {
                    throw new RuntimeException("Could not find just created user");
                }
                $flow_user = new FlowUser();
                $flow_user->base_user_id = $userId;
                $flow_user->flow_user_name = $base_username;
                $flow_user->flow_user_email = $base_email;
                $flow_user->save();
            }
        } catch (InvalidEmailException $e) {
            $message = 'Invalid email address';
        } catch (InvalidPasswordException $e) {
            $message = 'Invalid password';
        } catch (UserAlreadyExistsException $e) {
            $message = 'User already exists';
        } catch (TooManyRequestsException $e) {
            $message = 'Too many requests';
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
        } catch (AuthError $e) {
            $message = 'Auth:' . $e->getMessage();
        } catch (Exception $e) {
            $message = 'General' . $e->getMessage();
        }

        if (empty($message)) {
            try {

                static::add_flash_message('success',"Successfully Registered, please log in");
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('login');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to login after successful registration",['exception'=>$e]);
                throw $e;
            }
        } else {
            try {

                static::add_flash_message('danger',$message);
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('register');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to registration after problem registering",['exception'=>$e]);
                throw $e;
            }
        }
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function user_home( ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/user_home.twig',
                'page_title' => 'User Home',
                'page_description' => 'No Place Like Home',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render user_home",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function find_users_by_project( ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {

        $args = $request->getQueryParams();
        $term = null;
        if (isset($args['term'])) {
            $term = trim($args['term']);
        }
        $page = 1;
        if (isset($args['page'])) {
            $page_number = intval($args['page']);
            if ($page_number > 0) {
                $page = $page_number;
            }
        }
        $project_guid = null;
        if (isset($args['project_guid'])) {
            $project_guid = trim($args['project_guid']);
        }

        $in_project = true;
        if (isset($args['in_project']) && $project_guid) {
            $in_project = (bool)intval(($args['in_project']));
        }

        $matches = FlowUser::find_users_by_project($in_project,$project_guid,false,$term,$page);
        $b_more = true;
        if (count($matches) < FlowUser::DEFAULT_USER_PAGE_SIZE) {
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

    /**
     * @param ResponseInterface $response
     * @param string $user_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function user_page( ResponseInterface $response, string $user_name) :ResponseInterface {
        if ($this->user->id) {
            return $this->user_page_for_self( $response,$user_name);
        } else {
            return $this->user_page_for_others( $response,$user_name);
        }
    }



    /**
     * @param ResponseInterface $response
     * @param string $user_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function user_page_for_self( ResponseInterface $response, string $user_name) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/user_page_for_self.twig',
                'page_title' => 'User Page',
                'page_description' => 'No Place Like Home',
                'requested_user' => ['user_name'=>$user_name]
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render user_page_for_self",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ResponseInterface $response
     * @param string $user_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function user_page_for_others( ResponseInterface $response, string $user_name) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'user/user_page_for_others.twig',
                'page_title' => 'User Page',
                'page_description' => 'No Place Like Home',
                'user' => $this->user,
                'requested_user' => ['user_name'=>$user_name]
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render user_page_for_others",['exception'=>$e]);
            throw $e;
        }
    }
}