# Symfony Asynchronous Application
The application shows asynchronous interaction with services and microservices.

## Installation
```bash
make install
```

## Configuration
The web server is configured using [Nginx](https://nginx.org) and [Open Swoole](https://openswoole.com).  
Open Swoole is a set of solutions for building asynchronous and multi-threaded applications.  
This application uses solutions from Open Swoole: [HTTP Server](https://openswoole.com/docs/modules/swoole-http-server-doc) and [coroutines](https://openswoole.com/docs/modules/swoole-coroutine).  
Application developed on [Symfony](https://symfony.com) framework using [Swoole Runtime](https://github.com/php-runtime/swoole),  [Doctrine](https://www.doctrine-project.org) and the [HTTP Client](https://github.com/symfony/http-client) component.  
[PHP](https://www.php.net) uses the openswoole extension.  
[Supervisor](http://supervisord.org) is used to manage the start and restart of the web server and microservice.

## Application domain
Suppose there is a certain administration system and in one of the statistics sections there is a page showing information about orders.
