<?php

namespace App\Tests\Command;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\DataFixtures\UserFixtures;
use App\Service\PaymentService;
use App\Tests\AbstractTest;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

class MonthlyPaymentReportTest extends AbstractTest
{
    private $commandTester;
    private $messageLogger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

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

        /*$this->messageLogger = self::$container->get('mailer.default_transport');*/
        $this->serializer = self::$container->get('jms_serializer');

        $application = new Application(self::$kernel);
        $command = $application->find('payment:report');
        $this->commandTester = new CommandTester($command);

        $this->loadFixtures($this->getFixtures());
    }

    public function testExecute()
    {
        /// Начало 1 теста - верные данные -->
        $this->commandTester->execute(['date' => '2021-04-20']);

       /* $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::once())
            ->method('send');
        $twig = $this->createMock(Environment::class);
        $mailer = new Mailer($symfonyMailer, $twig);*/
        //$mailer->sendWelcomeMessage($user);

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
