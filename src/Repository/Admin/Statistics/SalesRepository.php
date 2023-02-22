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
