<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public const REF_PREFIX = 'user_';
    private const COUNT = 10;

    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // --- Create an ADMIN user ---
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setIsVerified(true);
        $manager->persist($admin);
        $this->addReference(self::REF_PREFIX.'admin', $admin);

        // --- Create a MODERATOR user ---
        $moderator = new User();
        $moderator->setUsername('moderator');
        $moderator->setEmail('moderator@example.com');
        $moderator->setRoles(['ROLE_MODERATOR']);
        $moderator->setPassword($this->passwordHasher->hashPassword($moderator, 'password'));
        $moderator->setIsVerified(true);
        $manager->persist($moderator);
        $this->addReference(self::REF_PREFIX.'moderator', $moderator);

        // --- Create a STUDENT user ---
        $student = new User();
        $student->setUsername('student');
        $student->setEmail('student@example.com');
        $student->setRoles(['ROLE_STUDENT']);
        $student->setPassword($this->passwordHasher->hashPassword($student, 'password'));
        $student->setIsVerified(true);
        $manager->persist($student);
        $this->addReference(self::REF_PREFIX.'student', $student);

        // --- Create random students ---
        for ($i = 0; $i < self::COUNT; $i++) {
            $user = new User();
            $user->setUsername($faker->userName());
            $user->setEmail($faker->unique()->safeEmail());
            $user->setRoles(['ROLE_STUDENT']); // most random users = students
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setIsVerified(true);

            $manager->persist($user);
            $this->addReference(self::REF_PREFIX.$i, $user);
        }

        $manager->flush();
    }
}
