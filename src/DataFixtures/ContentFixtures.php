<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Content;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContentFixtures extends Fixture implements DependentFixtureInterface
{
    public const CONTENT_REFERENCE_PREFIX = 'content_';

    private const ITEMS = [
        [
            'title'       => 'Getting Started with Symfony',
            'description' => 'A comprehensive introduction to the Symfony framework.',
            'file_path'   => 'uploads/videos/symfony-intro.mp4',
            'type'        => 'video',
            'user_index'  => 0,
            'cat_index'   => 0,
        ],
        [
            'title'       => 'Docker for PHP Developers',
            'description' => 'Learn how to containerise your PHP applications.',
            'file_path'   => 'uploads/videos/docker-php.mp4',
            'type'        => 'video',
            'user_index'  => 1,
            'cat_index'   => 0,
        ],
        [
            'title'       => 'REST API Best Practices',
            'description' => null,
            'file_path'   => 'uploads/docs/rest-api.pdf',
            'type'        => 'document',
            'user_index'  => 1,
            'cat_index'   => 3,
        ],
        [
            'title'       => 'Weekly Tech News #42',
            'description' => 'The most important tech stories of the week.',
            'file_path'   => 'uploads/articles/news-42.md',
            'type'        => 'article',
            'user_index'  => 0,
            'cat_index'   => 1,
        ],
        [
            'title'       => 'Review: PHP 8.4 Features',
            'description' => 'A deep dive into the new features shipped with PHP 8.4.',
            'file_path'   => 'uploads/articles/php84-review.md',
            'type'        => 'article',
            'user_index'  => 2,
            'cat_index'   => 2,
        ],
        [
            'title'       => 'Quantum Computing Explained',
            'description' => 'An accessible overview of quantum computing basics.',
            'file_path'   => 'uploads/videos/quantum.mp4',
            'type'        => 'video',
            'user_index'  => 3,
            'cat_index'   => 4,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::ITEMS as $i => $data) {
            $user     = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $data['user_index'], User::class);
            $category = $this->getReference(CategoryFixtures::CATEGORY_REFERENCE_PREFIX . $data['cat_index'], Category::class);

            $content = new Content();
            $content->setTitle($data['title']);
            $content->setDescription($data['description']);
            $content->setFilePath($data['file_path']);
            $content->setType($data['type']);
            $content->setCreatedAt(new \DateTime(sprintf('-%d days', ($i + 1) * 3)));
            $content->setFkUser($user);
            $content->setFkCategory($category);

            $manager->persist($content);
            $this->addReference(self::CONTENT_REFERENCE_PREFIX . $i, $content);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }
}
