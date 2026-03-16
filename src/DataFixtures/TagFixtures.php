<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends Fixture
{
    public const TAG_REFERENCE_PREFIX = 'tag_';

    private const NAMES = [
        'php',
        'symfony',
        'docker',
        'api',
        'frontend',
        'beginner',
        'advanced',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::NAMES as $i => $name) {
            $tag = new Tag();
            $tag->setName($name);

            $manager->persist($tag);
            $this->addReference(self::TAG_REFERENCE_PREFIX . $i, $tag);
        }

        $manager->flush();
    }
}
