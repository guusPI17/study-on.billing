<?php

namespace App\Tests\Command;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\AbstractTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;

class MonthlyPaymentReportTest extends AbstractTest
{
    private $commandTester;
    private $symfonyMailer;

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
        $command = $application->find('payment:report');
        $this->commandTester = new CommandTester($command);

        $this->loadFixtures($this->getFixtures());
    }


    private function serviceSubstitution(): void
    {
        self::getClient()->disableReboot();
        $this->symfonyMailer = $this->getMockBuilder(MailerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['send'])
            ->getMock();
        self::getClient()->getContainer()->set(MailerInterface::class, $this->symfonyMailer);
    }

    public function testExecute()
    {
        // подмена сервиса
        $this->serviceSubstitution();

        // один раз должен быть send
        $this->symfonyMailer->expects(self::once())
            ->method('send');

        /// Начало 1 теста - верные данные -->
        $this->commandTester->execute(['date' => '2021-04-20']);

        // получить вывод из консоли
        $output = $this->commandTester->getDisplay();

        // проверка результатов консоли
        self::assertStringContainsString('Команда успешно выполнена', $output);
        /// Конец 1 теста <--

        /// Начало 2 теста - не верный аргумент date -->
        $this->commandTester->execute(['date' => '1']);
        // получить вывод из консоли
        $output = $this->commandTester->getDisplay();
        // проверка результатов консоли
        self::assertStringContainsString('Не верный формат аргумента "date"', $output);
        /// Конец 2 теста <--
    }
}
