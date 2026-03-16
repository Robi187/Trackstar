<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\ContentTag;
use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContentTagFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Map: content_index => [tag_index, ...]
     */
    private const MAP = [
        0 => [0, 1, 5],   // symfony-intro   → php, symfony, beginner
        1 => [0, 2, 5],   // docker-php      → php, docker, beginner
        2 => [3, 6],       // rest-api        → api, advanced
        3 => [0, 1],       // news-42         → php, symfony
        4 => [0, 6],       // php84-review    → php, advanced
        5 => [6],          // quantum         → advanced
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::MAP as $contentIdx => $tagIndexes) {
            $content = $this->getReference(ContentFixtures::CONTENT_REFERENCE_PREFIX . $contentIdx, Content::class);

            foreach ($tagIndexes as $tagIdx) {
                $tag = $this->getReference(TagFixtures::TAG_REFERENCE_PREFIX . $tagIdx, Tag::class);

                $contentTag = new ContentTag();
                $contentTag->setFkContent($content);
                $contentTag->setFkTag($tag);

                $manager->persist($contentTag);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ContentFixtures::class,
            TagFixtures::class,
        ];
    }
}
