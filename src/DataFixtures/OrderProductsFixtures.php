<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderProductsFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $orders = $manager->getRepository(Order::class)->findAll();
        foreach ($orders as $order) {
            foreach (range(1, rand(1, 15)) as $_) {
                $orderProducts = new OrderProducts();
                $orderProducts->setOrd($order);
                $product = $manager->getRepository(Product::class)->find(rand(1, 5000));
                $orderProducts->setProduct($product);
    
                $manager->persist($orderProducts);
            }
        }
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OrderFixtures::class, ProductFixtures::class];
    }
}
