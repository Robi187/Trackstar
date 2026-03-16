<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_REFERENCE_PREFIX = 'user_';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email'    => 'admin@example.com',
                'username' => 'admin',
                'roles'    => ['ROLE_ADMIN'],
                'password' => 'admin1234',
            ],
            [
                'email'    => 'alice@example.com',
                'username' => 'alice',
                'roles'    => ['ROLE_USER'],
                'password' => 'alice1234',
            ],
            [
                'email'    => 'bob@example.com',
                'username' => 'bob',
                'roles'    => ['ROLE_USER'],
                'password' => 'bob12345',
            ],
            [
                'email'    => 'carol@example.com',
                'username' => 'carol',
                'roles'    => ['ROLE_USER'],
                'password' => 'carol123',
            ],
        ];

        foreach ($users as $i => $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));

            $manager->persist($user);
            $this->addReference(self::USER_REFERENCE_PREFIX . $i, $user);
        }

        $manager->flush();
    }
}
