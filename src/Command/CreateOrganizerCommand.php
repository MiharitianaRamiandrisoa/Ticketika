<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:organizer:create',
    description: 'Crée le premier compte organisateur de production.',
)]
final class CreateOrganizerCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $email = trim((string) $helper->ask($input, $output, new Question('E-mail : ')));
        $firstName = trim((string) $helper->ask($input, $output, new Question('Prénom : ')));
        $lastName = trim((string) $helper->ask($input, $output, new Question('Nom : ')));

        $passwordQuestion = new Question('Mot de passe (12 caractères minimum) : ');
        $passwordQuestion->setHidden(true);
        $password = (string) $helper->ask($input, $output, $passwordQuestion);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $firstName === '' || $lastName === '' || strlen($password) < 12) {
            $output->writeln('<error>Les informations sont invalides. Le mot de passe doit contenir au moins 12 caractères.</error>');

            return Command::INVALID;
        }
        if ($this->users->findOneByEmail($email) !== null) {
            $output->writeln('<error>Cette adresse e-mail existe déjà.</error>');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setPassword(password_hash($password, PASSWORD_DEFAULT))
            ->setOrganizer(true);
        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Compte organisateur créé.</info>');

        return Command::SUCCESS;
    }
}
