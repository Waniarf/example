<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $encoder)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create();

        $this->createUser($manager, 'api@example.com', [User::ROLE_USER], 'api');
        $this->createUser($manager, 'admin@example.com', [User::ROLE_ADMIN], 'admin');

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(
        ObjectManager $manager,
        string $email,
        array $roles,
        string $password,
    ): void {
        $user = new User($email, $roles);
        $user->setPassword($this->encoder->hashPassword($user, $password));

        $manager->persist($user);
    }
}
