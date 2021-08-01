# hexbatch-core-flow
Sets up the organization and work flow for the core

This is also a sub repo for https://github.com/hexbatch/hexbatch-core-flow
To create the php , server and db then go two levels up and do 
`make -C tools docker-up`

## Database 

The project uses migrations from the phoenix library. see https://github.com/lulco/phoenix
The migrations need to be done in the fpm container
Run `sudo docker exec -it hexbatch_dev_php-fpm_1 bash` and then inside do commands similar to

    php vendor/bin/phoenix create "hexlet_migrations\AddDelightIMUserSupport"
    php vendor/bin/phoenix status
    php vendor/bin/phoenix migrate      (--first ,--dry, others)
    php vendor/bin/phoenix rollback     (--dry, --all)

## PHP Libraries and Frameworks Used

### Installation Uses Composer
Composer is installed in the docker container of php-fpm
Run `sudo docker exec -it hexbatch_dev_php-fpm_1 bash` and then inside do composer command `composer install`

### Using Libraries 

https://github.com/slimphp/Slim For the overall framework
see https://discourse.slimframework.com
see https://www.slimframework.com/docs/v4
    using https://php-di.org/doc/frameworks/slim.html for container injection
    using https://github.com/slimphp/Slim-Psr7 for the PSR-7 implementation 
    using https://github.com/slimphp/Twig-View for twig views

https://github.com/lulco/phoenix for migrations
    https://symfony.com/doc/current/components/yaml.html for better config files

https://github.com/delight-im/PHP-Auth for user verification and authentication 

Twig 3.3 see https://twig.symfony.com/doc/3.x/


https://github.com/paragonie/easydb for easier db use

http://jbbcode.com/docs for bb code

https://github.com/paquettg/php-html-parser to help with bbcode

https://github.com/ramsey/uuid for making uuid in php

https://github.com/paragonie/anti-csrf

todo gmp in docker image


## Javascript Libraries Used

bootstrap https://getbootstrap.com/docs/4.1/getting-started/introduction/

select2  https://select2.org/
    with select2-bootstrap4-theme

bs4-toast https://github.com/Script47/Toast

tingle.js https://robinparisi.github.io/tingle/

jquery https://api.jquery.com/

lodash https://lodash.com/docs/

font-awesome-free  https://fontawesome.com/

sceditor to edit bbcode https://www.sceditor.com/

### installation uses npm
 Run `nmp i` to install js dependencies that are not saved in the repo
 

## Environmental Notes

The database connection is set as constants in the flow-config.php (not included in the repo):


### php ini and xdebug    
the docker php uses the php.ini loaded in from the tools/.docker/.shared/config/php folder to change it
restart the container. 

The xdebug is on the last section.
The important things to add to the docker-compose.yml file for xdebug to work is
       
        environment:
          - XDEBUG_SESSION=PHPSTORM
        extra_hosts:
          host.docker.internal: host-gateway


# Notes

"nesbot/carbon": "^2.39",
"neitanod/forceutf8": "^2.0",
"czproject/git-php": "^3.18",
"monolog/monolog": "^2.1",
"symfony/yaml": "^5.1",
"robmorgan/phinx": "^0.12.4",
"myclabs/deep-copy": "^1.10",
"dragonmantank/cron-expression": "^3.0",
"jmikola/geojson": "^1.0",
"ramsey/uuid": "^4.1",
"ozdemirburak/iris": "^2.1",
"hashids/hashids": "^4.0",

twig helpers

    url_for() - returns the URL for a given route. e.g.: /hello/world
    full_url_for() - returns the URL for a given route. e.g.: http://www.example.com/hello/world
    is_current_url() - returns true is the provided route name and parameters are valid for the current path.
    current_url() - returns the current path, with or without the query string.
    get_uri() - returns the UriInterface object from the incoming ServerRequestInterface object
    base_path() - returns the base path.
