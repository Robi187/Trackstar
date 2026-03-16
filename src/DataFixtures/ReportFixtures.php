<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\Reason;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReportFixtures extends Fixture implements DependentFixtureInterface
{
    private const ITEMS = [
        [
            'message'       => 'This video contains copyrighted music.',
            'status'        => 'pending',
            'user_index'    => 2,
            'reason_index'  => 2,
            'content_index' => 1,
            'comment_index' => null,
            'days_ago'      => 2,
        ],
        [
            'message'       => null,
            'status'        => 'pending',
            'user_index'    => 3,
            'reason_index'  => 4,
            'content_index' => null,
            'comment_index' => 4,
            'days_ago'      => 1,
        ],
        [
            'message'       => 'Looks like AI-generated misinformation.',
            'status'        => 'resolved',
            'user_index'    => 1,
            'reason_index'  => 3,
            'content_index' => 3,
            'comment_index' => null,
            'days_ago'      => 5,
        ],
        [
            'message'       => 'Spam links in the description.',
            'status'        => 'rejected',
            'user_index'    => 0,
            'reason_index'  => 0,
            'content_index' => 2,
            'comment_index' => null,
            'days_ago'      => 7,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::ITEMS as $data) {
            $user   = $this->getReference(UserFixtures::USER_REFERENCE_PREFIX . $data['user_index'], User::class);
            $reason = $this->getReference(ReasonFixtures::REASON_REFERENCE_PREFIX . $data['reason_index'], Reason::class);

            $content = $data['content_index'] !== null
                ? $this->getReference(ContentFixtures::CONTENT_REFERENCE_PREFIX . $data['content_index'], Content::class)
                : null;

            $comment = $data['comment_index'] !== null
                ? $this->getReference(CommentFixtures::COMMENT_REFERENCE_PREFIX . $data['comment_index'], Comment::class)
                : null;

            $report = new Report();
            $report->setMessage($data['message']);
            $report->setStatus($data['status']);
            $report->setCreatedAt(new \DateTime(sprintf('-%d days', $data['days_ago'])));
            $report->setFkUser($user);
            $report->setFkReason($reason);
            $report->setFkContent($content);
            $report->setFkComment($comment);

            $manager->persist($report);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ReasonFixtures::class,
            ContentFixtures::class,
            CommentFixtures::class,
        ];
    }
}
