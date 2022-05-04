# hexbatch-core-flow
Sets up the organization and work flow for the core hexbatch library. Can be used for other things too!

The main use of this web app is to organize ideas, its useful for planning complex specifications

## Releases
| Date              | Version | Name                                  |
|-------------------|---------|---------------------------------------|
| May 3, 2022       | 0.5.2   | Git Revamp                            |
| April 20, 2022    | 0.5.1   | Git Bit!                              |
| April 13, 2022    | 0.5.0.1 | Standard views and Edits              |
| March 30, 2022    | 0.5.0   | Standard Attributes are in the house! |
| March 26, 2022    | 0.4.4   | Working with tag attributes           |
| March 22, 2022    | 0.4.3   | Back into the workflow                |
| March 15, 2022    | 0.4.2   | Searching Things                      |
| October 31, 2021  | 0.4.1   | Entries First                         |
| October 26, 2021  | 0.4.0   | Tags Oh My!                           |
| September 2, 2021 | 0.3.0   | Import Export                         |
| August 9, 2021    | 0.2.0   | Projects and Users Exist              |
| July 4, 2021      | 0.1.0   | First Commit                          |

### See [Version History Page](versions.md)


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

If this is the first time running the project on this computer, also run `composer dump-autoload` 
as a second command to build the autoload class map so the classes in the src folder will load

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

https://github.com/cweagans/composer-patches to patch otherwise broken php-html-parser in 1.8.5 without fuss


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

Tempus Dominus at https://getdatepicker.com/ for chosing date time

SimpleLightbox at https://simplelightbox.com/ for showing original image when clicking on thumbnail

Chroma JS at https://gka.github.io/chroma.js/  to convert from named colors to hex

jQuery Serialize Object https://github.com/macek/jquery-serialize-object for converting form data reliably to a js object

### installation uses npm
 Run `npm i` to install js dependencies that are not saved in the repo
 

## Environmental Notes

The database connection is set as constants in the config/database.yaml (not included in the repo) 
but example at config/database-example.yaml , so can copy, fill that in, and rename


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

Below is a list of additional items that can be used in the twig markup 

### twig helpers from library

    url_for() - returns the URL for a given route. e.g.: /hello/world
    full_url_for() - returns the URL for a given route. e.g.: http://www.example.com/hello/world
    is_current_url() - returns true is the provided route name and parameters are valid for the current path.
    current_url() - returns the current path, with or without the query string.
    get_uri() - returns the UriInterface object from the incoming ServerRequestInterface object
    base_path() - returns the base path.


### Functions 

    form_token() - creates a safety token for ajax
    dump_session() - shows the current session contents (for debugging)
    uuid() - generates a new v4 uuid
    standard_keys() - returns an array of standard attribute keys for the given type

### Filters
    
    to_json_string  - A better and safer way of encoding json

#### Variables
    
    root_url - Full url to the top of the project
    csrf_token_set_to_root  - Used to create ajax CSRF protection
    program_version  -  current version number
    program_version_link - current version link for more info about this
    program_version_description - current version name
    supported_git_hosts - lists git sites can use ssh with inside project
            
    standard_viewable_scripts - lists standards that have javascript view support
    standard_editable_scripts - lists standards that have javascript edit support
    standard_viewable_frames - lists standards that have twig (html) view support
    standard_editable_frames - lists standards that have twig (html) edit support

# Notes

## GIT

In the php , the git repo is not on the computer , but on a mapped folder from docker,

when needing to do more git commands, either Run `sudo docker exec -it hexbatch_dev_php-fpm_1 bash` 
    and go up to the /var/www/flow_projects folder and then find the correct project repo there (they are listed by guid not name)

or to add code, php like this ` exec('cd /var/www/flow_projects/a/b && git status 2>&1',$output,$result_code);`

### Git hosts

The ssh to the hosts, when doing pushing or pulling with a remote repo, needs the host in the known_hosts file

The list of supported git sites for ssh is found in the config/settings.yaml under the supported_hosts key.
This simply is the list of which sites have this in the docker file at .docker/php-fpm/Dockerfile
        
    RUN (host=github.com; ssh-keyscan -H $host; for ip in $(dig @8.8.8.8 github.com +short); do ssh-keyscan -H $host,$ip; ssh-keyscan -H $ip; done) 2> /dev/null >> /home/www-data/.ssh/known_hosts;

To add another, just copy that line and switch out the website name, then add to the settings above

## Wishlist

There is a hook and filter library I really like, but am not including it now, as no need for it , and it has a LOT of dependencies

Hook and Action and Filter callback system
https://github.com/tormjens/eventy
see how to add here to non laraval project: https://github.com/tormjens/eventy/tree/master/tests/Unit  


