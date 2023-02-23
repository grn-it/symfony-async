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
