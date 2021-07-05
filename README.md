# hexbatch-core-flow
Sets up the organization and work flow for the core

## PHP Libraries and Frameworks Used

### Installation Uses Composer
Composer is installed in the docker container of php-fpm
Run `sudo docker exec -it hexbatch_dev_php-fpm_1 bash` and then inside do composer command `composer install`

### Using Libraries 

https://github.com/slimphp/Slim For the overall framework
see https://www.slimframework.com/docs/v4
    using https://php-di.org/doc/frameworks/slim.html for container injection
    using https://github.com/slimphp/Slim-Psr7 for the PSR-7 implementation 
    using https://github.com/slimphp/Twig-View for twig views

https://github.com/lulco/phoenix for migrations
    https://symfony.com/doc/current/components/yaml.html for better config files

https://github.com/delight-im/PHP-Auth for user verification and authentication 


## Javascript Libraries Used

### installation uses npm
 Run `nmp i` to install js dependencies that are not saved in the repo
 

## Environmental Notes
the docker php uses the php.ini loaded in from the tools/.docker/.shared/config/php folder to change it
restart the container. The xdebug is on the last section.
The important things to add to the docker-compose.yml file for xdebug to work is
       
        environment:
          - XDEBUG_SESSION=PHPSTORM
        extra_hosts:
          host.docker.internal: host-gateway