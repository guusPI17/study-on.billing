<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setRoles(['ROLE_USER']);
        $password = $this->passwordEncoder->encodePassword($user, 'user@test.com');
        $user->setPassword($password);
        $manager->persist($user);

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $password = $this->passwordEncoder->encodePassword($admin, 'admin@test.com');
        $admin->setPassword($password);
        $manager->persist($admin);

        $manager->flush();
    }
}