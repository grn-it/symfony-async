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
