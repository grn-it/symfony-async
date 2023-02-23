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

## Application Domain
Suppose there is a certain administration system and in one of the statistics sections there is a page showing information about sales.  

![sales](https://github.com/grn-it/assets/blob/main/symfony-async-app/sales.png)

The data from the <b>Total Sales</b> section was obtained from a [microservice](https://github.com/grn-it/symfony-async-app/blob/main/src/Microservice/Statistics/SalesMicroservice.php).  
The data from the <b>Sales</b> section was obtained from a [service](https://github.com/grn-it/symfony-async-app/blob/main/src/Controller/Service/Statistics/SalesController.php).  

## Asynchronous Interaction
When sending a request to [http://127.0.0.1/admin/statistics/sales](http://127.0.0.1/admin/statistics/sales), the controller is called:
```php
<?php

namespace App\Controller\Admin\Statistics;

use App\Dto\Microservice\Statistics\SalesTotalDto;
use App\Dto\Service\Statistics\SalesDto;
use App\Service\ServiceDeserializer;
use App\Service\Statistics\SalesServiceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SalesController extends AbstractController
{
    #[Route('/admin/statistics/sales', name: 'app_admin_statistics_sales')]
    public function index(
        SalesServiceClient $salesServiceClient,
        ServiceDeserializer $serviceDeserializer
    ): Response
    {
        $totalSalesResponse = $salesServiceClient->requestTotalSales();
        $salesResponse = $salesServiceClient->requestSales();

        return $this->render('statistics/sales.html.twig', [
            'totalSales' => $serviceDeserializer->deserialize($totalSalesResponse, SalesTotalDto::class),
            'sales' => $serviceDeserializer->deserialize($salesResponse, SalesDto::class.'[]')
        ]);
    }
}
```

The `index` action calls two requests asynchronously because the HTTP Client makes calls asynchronously [by default](https://symfony.com/doc/current/http_client.html#making-requests).  

General HTTP Client for services and microservices:
```php
<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Controller\Service\Statistics\SalesController;

class SalesServiceClient
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Microservice
     * @see SalesMicroservice
     */
    public function requestTotalSales(): ResponseInterface
    {
        return $this->httpClient->request('GET', 'http://127.0.0.1:9501', ['timeout' => 3]);
    }

    /**
     * Service
     * @see SalesController::index
     */
    public function requestSales(): ResponseInterface
    {
        return $this->httpClient->request('GET', 'http://nginx/service/statistics/sales', ['timeout' => 3]);
    }
}
```

### Total Sales Microservice
This microservice makes multiple SQL queries asynchronously using [coroutines](https://openswoole.com/docs/modules/swoole-coroutine).  
Coroutines use a special [Postgres client](https://openswoole.com/docs/modules/swoole-coroutine-postgres) from Open Swoole, which, when executing an SQL query, does not block code execution, but transfers control further to the next coroutine.  
Using this asynchrony, the query processing speed will be equal to the longest SQL query of all.  
This means that asynchrony has reduced the processing time of the request and the optimal use of processor resources. 

```php
<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Dto\Microservice\Statistics\SalesTotalDto;
use OpenSwoole\Core\Coroutine\WaitGroup;

class SalesMicroservice
{
    private OpenSwoole\HTTP\Server $server;
    
    public function __construct()
    {
        $this->server = new OpenSwoole\HTTP\Server("127.0.0.1", 9501);
        $this->server->set(['worker_num' => 4]);
        
        $this->onRequest();
        $this->server->start();
        
    }

    public function getPg(): OpenSwoole\Coroutine\PostgreSQL
    {
        $pg = new OpenSwoole\Coroutine\PostgreSQL();
        $connection = $pg->connect('host=database;dbname=app;user=app;password=!ChangeMe!');
        if (!$connection) {
            throw new RuntimeException('Could not connect to database');
        }

        return $pg;
    }
    
    private function onRequest(): void
    {
        $_this = $this;
        $this->server->on('Request', function(
            OpenSwoole\Http\Request $request,
            OpenSwoole\Http\Response $response
        ) use ($_this)
        {
            $waitGroup = new WaitGroup();
            $users = 0;
            $buyers = 0;
            $orders = 0;
            $products = 0;
            $income = 0;

            Swoole\Coroutine::create(static function() use ($waitGroup, $_this, &$users) {
                $waitGroup->add();

                $pg = $_this->getPg();
                $query = $pg->query('SELECT COUNT(*) FROM "user"');
                $users = $pg->fetchAssoc($query)['count'];

                $waitGroup->done();
            });

            Swoole\Coroutine::create(static function() use ($waitGroup, $_this, &$buyers) {
                $waitGroup->add();

                $pg = $_this->getPg();
                $query = $pg->query('SELECT COUNT(DISTINCT(buyer_id)) FROM "order"');
                $buyers = $pg->fetchAssoc($query)['count'];

                $waitGroup->done();
            });

            Swoole\Coroutine::create(static function() use ($waitGroup, $_this, &$orders) {
                $waitGroup->add();

                $pg = $_this->getPg();
                $query = $pg->query('SELECT COUNT(*) FROM "order"');
                $orders = $pg->fetchAssoc($query)['count'];

                $waitGroup->done();
            });

            Swoole\Coroutine::create(static function() use ($waitGroup, $_this, &$products) {
                $waitGroup->add();

                $pg = $_this->getPg();
                $query = $pg->query('SELECT COUNT(DISTINCT(product_id)) FROM order_products');
                $products = $pg->fetchAssoc($query)['count'];

                $waitGroup->done();
            });

            Swoole\Coroutine::create(static function() use ($waitGroup, $_this, &$income) {
                $waitGroup->add();

                $pg = $_this->getPg();
                $query = $pg->query('
                    SELECT SUM(p.price) FROM order_products op 
                    LEFT JOIN product p ON op.product_id = p.id
                ');
                $income = $pg->fetchAssoc($query)['sum'];
                
                $waitGroup->done();
            });
            
            $waitGroup->wait(3);

            $response->end(
                json_encode(
                    new SalesTotalDto($users, $buyers, $orders, $products, $income)
                )
            );
        });
    }
}

(new SalesMicroservice());
```
