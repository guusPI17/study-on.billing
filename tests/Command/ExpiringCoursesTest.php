<?php

namespace App\Tests\Command;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\AbstractTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ExpiringCoursesTest extends AbstractTest
{
    private $commandTester;

    protected function getFixtures(): array
    {
        return [
            UserFixtures::class,
            CourseFixtures::class,
            TransactionFixtures::class,
        ];
    }

    protected function setUp(): void
    {
        static::getClient();

        $application = new Application(self::$kernel);
        $command = $application->find('payment:ending:notification');
        $this->commandTester = new CommandTester($command);

        $this->loadFixtures($this->getFixtures());
    }

    public function testExecute()
    {
        $this->commandTester->execute([]);
        // получить вывод из консоли
        $output = $this->commandTester->getDisplay();

        // проверка результатов консоли
        self::assertStringContainsString('Команда успешно выполнена', $output);
    }
}
