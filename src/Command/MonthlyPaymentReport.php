<?php

namespace App\Command;

use App\Entity\Course;
use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MonthlyPaymentReport extends Command
{
    private $twig;
    private $mailer;
    private $em;

    protected static $defaultName = 'payment:report';

    public function __construct(
        Twig $twig,
        MailerInterface $mailer,
        EntityManagerInterface $em
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->em = $em;
        parent::__construct();
    }

    private function generationHtml(): string
    {
        $courses = $this->em
            ->getRepository(Course::class)
            ->findMonthlyPaymentReport()
        ;

        $totalAmount = 0;
        foreach ($courses as $course) {
            $totalAmount += $course['summa'];
        }
        $html = $this->twig->render(
            'monthlyPaymentReport.html.twig',
            [
                'courses' => $courses,
                'totalAmount' => $totalAmount,
                'date' => (new \DateTime())->format('d-m-Y'),
                'datePlusMonth' => (new \DateTime('+ 1 month'))->format('d-m-Y'),
            ]
        );

        return $html;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = (new Email())
            ->to()
            ->subject('Отчет по оплатам за месяц')
            ->html($this->generationHtml());

        try {
            $this->mailer->send($email);
            $output->writeln('Команда успешно выполнена');

            return Command::SUCCESS;
        } catch (TransportExceptionInterface $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }
}
