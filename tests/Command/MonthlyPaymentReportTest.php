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
    private $passwordEncoder;
    private $paymentService;
    private $em;
    private $commandTester;
    private $messageLogger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function getFixtures(): array
    {
        return [
            new UserFixtures($this->passwordEncoder, $this->paymentService),
            CourseFixtures::class,
            TransactionFixtures::class,
        ];
    }

    protected function setUp(): void
    {
        static::getClient();

        $this->passwordEncoder = self::$container->get('security.password_encoder');
        $this->messageLogger = self::$container->get('mailer.default_transport');
        $this->em = self::$container->get('doctrine')->getManager();
        $this->paymentService = self::$container->get(PaymentService::class);
        $this->serializer = self::$container->get('jms_serializer');

        $application = new Application(self::$kernel);
        $command = $application->find('payment:report');
        $this->commandTester = new CommandTester($command);

        $this->loadFixtures($this->getFixtures());
    }

    public function testExecute()
    {
        $this->commandTester->execute([]);

        /*$symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects(self::once())
            ->method('send');
        $twig = $this->createMock(Environment::class);
        $mailer = new Mailer($symfonyMailer, $twig);
        //$mailer->sendWelcomeMessage($user);


        var_dump($mailer);exit;*/

        // получить вывод из консоли
        $output = $this->commandTester->getDisplay();

        // проверка результатов консоли
        self::assertStringContainsString('Команда успешно выполнена', $output);
    }
}