<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Reason;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ReasonFixtures extends Fixture
{
    public const REASON_REFERENCE_PREFIX = 'reason_';

    private const NAMES = [
        'Spam',
        'Inappropriate content',
        'Copyright violation',
        'Misinformation',
        'Harassment',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::NAMES as $i => $name) {
            $reason = new Reason();
            $reason->setName($name);

            $manager->persist($reason);
            $this->addReference(self::REASON_REFERENCE_PREFIX . $i, $reason);
        }

        $manager->flush();
    }
}
