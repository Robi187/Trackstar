<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_REFERENCE_PREFIX = 'category_';

    private const NAMES = [
        'Tutorials',
        'News',
        'Reviews',
        'Entertainment',
        'Science',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::NAMES as $i => $name) {
            $category = new Category();
            $category->setName($name);

            $manager->persist($category);
            $this->addReference(self::CATEGORY_REFERENCE_PREFIX . $i, $category);
        }

        $manager->flush();
    }
}
