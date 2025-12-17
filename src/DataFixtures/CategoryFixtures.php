<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categoryList = ['Entrée', 'Plat', 'Fromage', 'Dessert'];
        
        foreach ($categoryList as $categoryOne) {
            
            $category = new Category();
            $category->setName($categoryOne);
            $manager->persist($category);
            
        }

        $manager->flush();
    }
}
