<?php

namespace App\DataFixtures;

use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture
{
    private const TYPES_OPERATION = [
        1 => 'payment',
        2 => 'deposit',
    ];

    public function load(ObjectManager $manager)
    {
        $transactions = [
            [
                'course' => $this->getReference('deep_learning'),
                'user' => $this->getReference('accountAdmin'),
                'typeOperation' => 1,
                'amount' => $this->getReference('deep_learning')->getPrice(),
                'expiresAt' => (new \DateTime())->add(new \DateInterval('P1W')),
                'createdAt' => (new \DateTime('-1 day')),
            ],
            [
                'course' => $this->getReference('design_course'),
                'user' => $this->getReference('accountAdmin'),
                'typeOperation' => 1,
                'amount' => $this->getReference('design_course')->getPrice(),
                'expiresAt' => (new \DateTime('-2 day')),
                'createdAt' => (new \DateTime('-3 day')),
            ],
            [
                'course' => $this->getReference('python_course'),
                'user' => $this->getReference('accountAdmin'),
                'typeOperation' => 1,
                'amount' => $this->getReference('python_course')->getPrice(),
                'expiresAt' => (new \DateTime())->add(new \DateInterval('P1W')),
                'createdAt' => (new \DateTime()),
            ],
        ];

        foreach ($transactions as $dataTransaction) {
            $transaction = new Transaction();
            $transaction->setCourse($dataTransaction['course']);
            $transaction->setUser($dataTransaction['user']);
            $transaction->setTypeOperation($dataTransaction['typeOperation']);
            $transaction->setAmount($dataTransaction['amount']);
            $transaction->setExpiresAt($dataTransaction['expiresAt']);
            $transaction->setCreatedAt($dataTransaction['createdAt']);

            $manager->persist($transaction);
        }

        $manager->flush();
    }
}
