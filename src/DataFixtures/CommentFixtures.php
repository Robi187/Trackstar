<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public const COMMENT_REFERENCE_PREFIX = 'comment_';

    private const ITEMS = [
        [
            'text'          => 'Great tutorial, really helped me get started!',
            'user_index'    => 1,
            'content_index' => 0,
            'days_ago'      => 5,
        ],
        [
            'text'          => 'Could you also cover Symfony Messenger?',
            'user_index'    => 2,
            'content_index' => 0,
            'days_ago'      => 4,
        ],
        [
            'text'          => 'Docker Compose examples would have been nice.',
            'user_index'    => 3,
            'content_index' => 1,
            'days_ago'      => 3,
        ],
        [
            'text'          => 'Very clear explanation of REST principles.',
            'user_index'    => 0,
            'content_index' => 2,
            'days_ago'      => 2,
        ],
        [
            'text'          => 'I disagree with the versioning approach mentioned here.',
            'user_index'    => 3,
            'content_index' => 2,
            'days_ago'      => 1,
        ],
        [
            'text'          => 'Named arguments are my favourite PHP 8.x feature so far.',
            'user_index'    => 1,
            'content_index' => 4,
            'days_ago'      => 2,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::ITEMS as $i => $data) {
            $user    = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $data['user_index'], User::class);
            $content = $this->getReference(ContentFixtures::CONTENT_REFERENCE_PREFIX . $data['content_index'], Content::class);

            $comment = new Comment();
            $comment->setText($data['text']);
            $comment->setCreatedAt(new \DateTime(sprintf('-%d days', $data['days_ago'])));
            $comment->setFkUser($user);
            $comment->setFkContent($content);

            $manager->persist($comment);
            $this->addReference(self::COMMENT_REFERENCE_PREFIX . $i, $comment);
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
