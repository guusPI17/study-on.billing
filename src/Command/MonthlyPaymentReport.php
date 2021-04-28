<?php

namespace App\Command;

use App\Entity\Course;
use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class MonthlyPaymentReport extends Command
{
    private $twig;
    private $mailer;
    private $em;

    protected static $defaultName = 'payment:report';

    public function __construct(
        Twig $twig,
        TransportInterface $mailer,
        EntityManagerInterface $em
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('date',
            InputArgument::REQUIRED,
            'Требуется ввести дату начала генерации отчета'
        );
    }

    private function generationHtml(string $date): string
    {
        try {
            $dateStart = (new \DateTime($date))->format('d-m-Y');
            $dateEnd = (new \DateTime("$date + 1 month"))->format('d-m-Y');
        } catch (\Exception $e) {
            throw new \Exception('Не верный формат аргумента "date"');
        }

        $courses = $this->em
            ->getRepository(Course::class)
            ->findMonthlyPaymentReport((new \DateTime($date)));

        $totalAmount = 0;
        foreach ($courses as $course) {
            $totalAmount += $course['summa'];
        }
        $html = $this->twig->render(
            'monthlyPaymentReport.html.twig',
            [
                'courses' => $courses,
                'totalAmount' => $totalAmount,
                'date' => $dateStart,
                'datePlusMonth' => $dateEnd,
            ]
        );

        return $html;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $email = (new Email())
                ->to()
                ->subject('Отчет по оплатам за месяц')
                ->html($this->generationHtml($input->getArgument('date')));

            $this->mailer->send($email);
            $output->writeln('Команда успешно выполнена');

            return Command::SUCCESS;
        } catch (TransportExceptionInterface $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }
}
