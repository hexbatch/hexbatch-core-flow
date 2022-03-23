# hexbatch-core-flow
Sets up the organization and work flow for the core hexbatch library. Can be used for other things too!

The main use of this web app is to organize ideas, its useful for planning complex specifications

## Releases
| Date              | Version | Name                     |
|-------------------|---------|--------------------------|
| October 26, 2021  | 0.4.0   | Tags Oh My!              |
| September 2, 2021 | 0.3.0   | Import Export            |
| August 9, 2021    | 0.2.0   | Projects and Users Exist |
| July 4, 2021      | 0.1.0   | First Commit             |


### Tags Oh My!  ~ Released October 26, 2021

Each project can create and manage a list of tags

A tag has a name , a possible parent tag to inherit from, and a guid (and some other things like timestamps)

Each tag can have 0 or more attributes. An attribute can have a name, an integer value and a text value. 
An attribute can also link to any one user or entry or project. 
A tag will inherit all the attributes of its ancestors, with the ability to overwrite exiting tags for itself and its own descendants.
Some attributes are used to style the tag name or things the tag points too, or when a tag is applied to something.
These are called standard attributes. standard attributes of color, background-color are currently used to change the tag name

Projects, Users and Entries can be assigned tags. This is called applying tags.
Tags are assigned by any project but if a project is visible the tag names and properties are too

### Import Export ~ Released September 2,2021

Save projects to an internal repository, see the commits on a web page, auto push to remote repo for each save

Can import a new branch , or commit of the same repo (as long as derived from current branch )


## Installation
This project is also a sub repo for https://github.com/hexbatch/hexbatch-core-flow.
Download that repo, it will have all the docker composer things to setup the environment.
This project is designed to run in these linux containers, with this database, server and php installation .
The code can be directly worked on because of a mapping between htdocs, the host computer, the php-fpm, and the ngix

The development database and repositories for the projects are inside docker volumns, which can be migrated.
The development version of this project has a db and repo volume, saved and downloaded from a aws bucket, for easy startup 

To create the php , server and db, go two levels up from this and do 
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

If this is the first time running the project on this computer, also run `composer dump-autoload` as a second command to build the autoload class map so the classes in the src folder will load

### Using PHP Libraries 

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

https://github.com/paragonie/anti-csrf for protection against cross site attacks

https://github.com/scrivo/highlight.php for syntax highlighting of bb code blocks when converting to html

https://github.com/BlueM/Tree for those hard to reach sorting issues

https://github.com/php-lock/lock for making sure only one thing at a time can do critical file changes

https://carbon.nesbot.com for date/time strings

## Javascript Libraries Used

bootstrap https://getbootstrap.com/docs/4.1/getting-started/introduction/
    
boostrap icons https://icons.getbootstrap.com/

select2  https://select2.org/
    with select2-bootstrap4-theme

bs4-toast https://github.com/Script47/Toast

tingle.js https://robinparisi.github.io/tingle/

jquery https://api.jquery.com/

lodash https://lodash.com/docs/

font-awesome-free  https://fontawesome.com/

sceditor to edit bbcode https://www.sceditor.com/

diff2html for showing diffs in a nice way , see https://diff2html.xyz/index.html

sweetalert2 for confirm boxes and simple popups https://sweetalert2.github.io/

uuid for creating those sweet sweet uuids in the browser https://www.npmjs.com/package/uuid

copy to clipboard at https://clipboardjs.com because I deserve to not have to make yet another handcrafted one, and this looks good

swiper at https://swiperjs.com/ for nice looking media display

dropzone at https://docs.dropzone.dev/ for uploading files

### installation uses npm
 Run `npm i` to install js dependencies that are not saved in the repo
 

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





# Project Layout

## All html markup is done by twig templates the views folder

### twig helpers

    url_for() - returns the URL for a given route. e.g.: /hello/world
    full_url_for() - returns the URL for a given route. e.g.: http://www.example.com/hello/world
    is_current_url() - returns true is the provided route name and parameters are valid for the current path.
    current_url() - returns the current path, with or without the query string.
    get_uri() - returns the UriInterface object from the incoming ServerRequestInterface object
    base_path() - returns the base path.

### custom twig helpers

    form_token() - creates a safety token for ajax
    dump_session() - shows the current session contents (for debugging)
    uuid() - generates a new v4 uuid

# Notes

## GIT
in the php , the git repo is not on the computer , but on a mapped folder from docker,
when needing to do more git commands, either Run `sudo docker exec -it hexbatch_dev_php-fpm_1 bash` 
    and go up to the /var/www/flow_projects folder and then find the correct project repo there (they are listed by guid not name)

or to add code, php like this ` exec('cd /var/www/flow_projects/a/b && git status 2>&1',$output,$result_code);`


## Wishlist

There is a hook and filter library I really like, but am not including it now, as no need for it , and it has a LOT of dependencies

Hook and Action and Filter callback system
https://github.com/tormjens/eventy
see how to add here to non laraval project: https://github.com/tormjens/eventy/tree/master/tests/Unit  


