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
