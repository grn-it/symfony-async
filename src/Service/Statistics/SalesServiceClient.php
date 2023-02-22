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
