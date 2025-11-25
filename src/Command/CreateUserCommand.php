<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a test user',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = 'demo';
        $password = 'tarot2024';

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        
        if ($existingUser) {
            $io->warning('Un utilisateur avec ce pseudo existe déjà.');
            $io->success('Utilisateur existant :');
            $io->text('Pseudo: ' . $username);
            $io->text('Mot de passe: tarot2024');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setRoles(['ROLE_USER']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Utilisateur créé avec succès !');
        $io->section('Informations de connexion :');
        $io->text('Pseudo: ' . $username);
        $io->text('Mot de passe: ' . $password);

        return Command::SUCCESS;
    }
}
