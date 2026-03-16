<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RatingFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Each entry: [user_index, content_index, value (1-5)]
     */
    private const ITEMS = [
        [1, 0, 5],
        [2, 0, 4],
        [3, 0, 5],
        [0, 1, 3],
        [2, 1, 4],
        [1, 2, 5],
        [3, 2, 4],
        [0, 3, 3],
        [1, 4, 5],
        [2, 5, 4],
        [3, 5, 2],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::ITEMS as [$userIdx, $contentIdx, $value]) {
            $user    = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $userIdx, User::class);
            $content = $this->getReference(ContentFixtures::CONTENT_REFERENCE_PREFIX . $contentIdx, Content::class);

            $rating = new Rating();
            $rating->setValue($value);
            $rating->setFkUser($user);
            $rating->setFkContent($content);

            $manager->persist($rating);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ContentFixtures::class,
        ];
    }
}
