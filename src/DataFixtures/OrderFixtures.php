<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        
        foreach (range(1, rand(1000, 1300)) as $_) {
            $order = new Order();
            $buyer = $manager->getRepository(User::class)->find(rand(1, 60));
            $order->setBuyer($buyer);
            $order->setCreatedAt($faker->dateTime);

            $manager->persist($order);
        }
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
