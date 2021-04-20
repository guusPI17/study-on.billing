<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface, ContainerAwareInterface
{
    /** @var Container */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public static function getGroups(): array
    {
        return ['group1'];
    }

    public function load(ObjectManager $manager)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');
        $paymentService = $this->container->get('App\Service\PaymentService');

        $user = new User();
        $user->setEmail('user@test.com');
        $user->setRoles(['ROLE_USER']);
        $password = $passwordEncoder->encodePassword($user, 'user@test.com');
        $user->setPassword($password);
        $manager->persist($user);
        $paymentService->refill($user, 200);

        $this->addReference('accountUser', $user);

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $password = $passwordEncoder->encodePassword($admin, 'admin@test.com');
        $admin->setPassword($password);
        $admin->setBalance(0);
        $manager->persist($admin);
        $paymentService->refill($admin, 200);

        $this->addReference('accountAdmin', $admin);

        $manager->flush();
    }
}
