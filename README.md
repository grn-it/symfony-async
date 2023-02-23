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

Microservices are used when you need to perform simple operations that do not require a framework, ORM and components.  
Services are used for more complex operations.  

### Sales Service
This service uses a framework and an ORM to build a SQL query with search, filter and sort features.

A few examples:  
http://127.0.0.1/service/statistics/sales?search[lastname]=Smi  
http://127.0.0.1/service/statistics/sales?filter[price][lower]=1000  
http://127.0.0.1/service/statistics/sales?order[lastname]=asc  

```php
<?php

declare(strict_types=1);

namespace App\Controller\Service\Statistics;

use App\Dto\Parameter\ParameterCreator;
use App\Dto\Service\Statistics\SalesListDto;
use App\Repository\Admin\Statistics\SalesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SalesController extends AbstractController
{
    #[Route('/service/statistics/sales', name: 'app_service_statistics_sales')]
    public function index(
        SalesRepository $salesRepository,
        ParameterCreator $parameterCreator,
        Request $request
    ): Response
    {
        $sales = $salesRepository->findSales(
            $parameterCreator->createSearch($request),
            $parameterCreator->createFilterList($request),
            $parameterCreator->createOrder($request)
        );

        return $this->json(
            (new SalesListDto($sales))->getSales()
        );
    }
}
```

#### Sales Repository
The `findSales` method allows to search, filter, and sort.

```php
<?php

namespace App\Repository\Admin\Statistics;

use App\Dto\Parameter\FilterList;
use App\Dto\Parameter\Order;
use App\Dto\Parameter\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Order as OrderEntity;

class SalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderEntity::class);
    }

    public function findSales(?Search $search, ?FilterList $filterList, ?Order $order): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select(['
                b.id, 
                b.firstname as firstname, 
                b.lastname as lastname, 
                b.email, 
                count(distinct(o.id)) as orderCount,
                sum(p.price) as orderSum,
                count(p.id) as productCount
            '])
            ->leftJoin('o.buyer', 'b')
            ->leftJoin('o.orderProducts', 'op')
            ->leftJoin('op.product', 'p')
            ->groupBy('b.id')
        ;
        
        if ($search && $search->hasName(['firstname', 'lastname'])) {
            $qb->andWhere(
                $qb->expr()->like(
                    sprintf('lower(b.%s)', $search->getName()),
                    $qb->expr()->literal(
                        sprintf('%%%s%%', strtolower($search->getValue()))
                    )
                )
            );
        }
        
        if ($filterList && $filterList->hasName(['email', 'id', 'price'])) {
            foreach ($filterList->getItems() as $filter) {
                switch ($filter->getName()) {
                    case 'email':
                    case 'id':
                        $qb->andWhere(sprintf('b.%s = :%s', $filter->getName(), $filter->getName()))
                            ->setParameter($filter->getName(), $filter->getValue());
                        break;
                        
                    case 'price':
                        if ($filter->isValueString()) {
                            $qb->andWhere('p.price < :price')
                            ->setParameter('price', $filter->getValue());
                        } elseif ($filter->isValueArray()) {
                            $filter->hasKey(['greater', 'lower']);

                            $priceGreater = $filter->getValue()['greater'] ?? null;
                            if ($priceGreater) {
                                $qb->andWhere('p.price > :priceGreater')
                                    ->setParameter('priceGreater', $priceGreater);
                                
                            }
                            
                            $priceLower = $filter->getValue()['lower'] ?? null;
                            if ($priceLower) {
                                $qb->andWhere('p.price < :priceLower')
                                    ->setParameter('priceLower', $priceLower);
                            }
                        }
                        break;
                }
            }
        }
        
        if ($order && $order->hasName(['firstname', 'lastname', 'orderSum']) && $order->hasValue(['asc', 'desc'])) {
            $qb->orderBy($order->getName(), $order->getValue());
        } else {
            $qb->orderBy('orderSum', 'desc');
        }

        return $qb->getQuery()->getArrayResult();
    }
}
```
