<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FavoriteFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Each entry: [user_index, content_index]
     * The composite PK (fk_user_id, fk_content_id) must be unique.
     */
    private const PAIRS = [
        [1, 0],
        [1, 2],
        [2, 0],
        [2, 3],
        [3, 1],
        [3, 4],
        [0, 5],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PAIRS as [$userIdx, $contentIdx]) {
            $user    = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $userIdx, User::class);
            $content = $this->getReference(ContentFixtures::CONTENT_REFERENCE_PREFIX . $contentIdx, Content::class);

            $favorite = new Favorite();
            $favorite->setFkUser($user);
            $favorite->setFkContent($content);

            $manager->persist($favorite);
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
