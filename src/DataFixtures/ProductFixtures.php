<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        
        foreach (range(1, 5000) as $_) {
            $product = new Product();
            $product->setName($faker->text(30));
            $product->setPrice(rand(100, 10000));

            $manager->persist($product);
        }
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OrderFixtures::class];
    }
}
