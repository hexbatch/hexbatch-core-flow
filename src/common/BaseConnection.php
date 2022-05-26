<?php

namespace app\common;

use app\models\user\IFlowUser;
use app\models\user\IFlowUserAuth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use ParagonIE\EasyDB\EasyDB;
use Slim\Views\Twig;
use stdClass;

class BaseConnection {
    protected IFlowUserAuth $auth;
    protected Logger $logger;
    /**
     * @var Container $container
     */
    protected Container $container;

    protected Twig $view;

    protected IFlowUser $user;

    private static ?Container $dat_container = null;

    protected static function get_container() : Container { return static::$dat_container;}

    /**
     * UserLogInPages constructor.
     * @param IFlowUserAuth $auth
     * @param Logger $logger
     * @param Container $container
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function __construct(IFlowUserAuth $auth, Logger $logger, Container $container)
    {
        static::$dat_container = $container;
        $this->auth = $auth;
        $this->logger = $logger;
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->user = $this->container->get('user');
    }

   public function get_current_user() : ?IFlowUser{
        return $this->user;
   }

    protected function get_logger() : Logger {
        return $this->logger;
    }

    /**
     * @return EasyDB
     */
    protected function get_connection() : EasyDB {
        try {
            return  $this->container->get('connection');
        } catch (Exception $e) {
            $this->logger->alert("User model cannot connect to the database",['exception'=>$e]);
            die( static::class . " Cannot get connection");
        }
    }


    /**
     * @return stdClass
     */
    protected function get_settings() : stdClass  {
        try {
            return  $this->container->get('settings');
        } catch (Exception $e) {
            $this->logger->alert("cannot get settings",['exception'=>$e]);
            die( static::class . " Cannot get settings");
        }
    }


}