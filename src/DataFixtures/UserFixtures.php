<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;
    private $paymentService;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, PaymentService $paymentService)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->paymentService = $paymentService;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setRoles(['ROLE_USER']);
        $password = $this->passwordEncoder->encodePassword($user, 'user@test.com');
        $user->setPassword($password);
        $manager->persist($user);
        $this->paymentService->refill($user, 200);

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $password = $this->passwordEncoder->encodePassword($admin, 'admin@test.com');
        $admin->setPassword($password);
        $admin->setBalance(0);
        $manager->persist($admin);
        $this->paymentService->refill($admin, 200);

        $manager->flush();
    }
}
