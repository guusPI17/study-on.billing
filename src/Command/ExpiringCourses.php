<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\User;
use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ExpiringCourses extends Command
{
    private $twig;
    private $mailer;
    private $em;

    protected static $defaultName = 'payment:ending:notification';

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

    private function generationHtml(User $user): ?string
    {
        $courses = $this->em
            ->getRepository(Course::class)
            ->findExpiringCourses($user);
        if (0 < count($courses)) {
            $html = $this->twig->render(
                'expiringCourses.html.twig',
                ['courses' => $courses]
            );

            return $html;
        }

        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->em->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $html = $this->generationHtml($user);
            if ($html) {
                $email = (new Email())
                    ->to($user->getUsername())
                    ->subject('Уведомление об окончании срока аренды')
                    ->html($html);
                try {
                    $this->mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    $output->writeln($e->getMessage());

                    return Command::FAILURE;
                }
            }
        }
        $output->writeln('Команда успешно выполнена');

        return Command::SUCCESS;
    }
}
