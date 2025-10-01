<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Promote a user to student/moderator/admin (adds or sets roles).',
)]
class UserPromoteCommand extends Command
{
    /**
     * Mapping simple -> rôle Symfony
     */
    private const ROLE_MAP = [
        'student'   => 'ROLE_STUDENT',
        'moderator' => 'ROLE_MODERATOR',
        'admin'     => 'ROLE_ADMIN',
    ];

    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // username ou email
            ->addArgument('identifier', InputArgument::OPTIONAL, 'Username or email')
            // student | moderator | admin
            ->addArgument('role', InputArgument::OPTIONAL, 'Role: student | moderator | admin')
            // si présent, on remplace tous les rôles existants par ce rôle
            ->addOption('set', null, InputOption::VALUE_NONE, 'Replace all existing roles instead of adding')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);

        // --- 1) Récupération interactive si non fournie ---
        $identifier = $input->getArgument('identifier')
            ?: $io->ask('Username or email');

        if (!$identifier) {
            $io->error('Missing identifier. Provide a username or an email.');
            return Command::FAILURE;
        }

        $roleKey = $input->getArgument('role')
            ?: $io->choice('Select role', array_keys(self::ROLE_MAP), 'student');

        $roleKey = strtolower(trim($roleKey));
        if (!isset(self::ROLE_MAP[$roleKey])) {
            $io->error(sprintf('Invalid role "%s". Allowed: %s', $roleKey, implode(', ', array_keys(self::ROLE_MAP))));
            return Command::FAILURE;
        }
        $targetRole = self::ROLE_MAP[$roleKey];

        $replace = (bool) $input->getOption('set');

        // --- 2) Recherche utilisateur (username puis email) ---
        /** @var User|null $user */
        $user = $this->users->findOneBy(['username' => $identifier])
            ?? $this->users->findOneBy(['email' => $identifier]);

        if (!$user) {
            $io->error(sprintf('User "%s" not found by username or email.', $identifier));
            return Command::FAILURE;
        }

        $io->text(sprintf('Found user: <info>%s</info> (email: %s)', $user->getUserIdentifier(), $user->getEmail() ?? '—'));

        // --- 3) Calcul des nouveaux rôles ---
        $storedRoles = $this->getStoredRoles($user); // sans ROLE_USER implicite
        $newRoles = $replace ? [$targetRole] : $this->addRoleIfMissing($storedRoles, $targetRole);

        // Rien à faire ?
        if (!$replace && $storedRoles === $newRoles) {
            $io->note(sprintf('User already has role %s. Nothing changed.', $targetRole));
            return Command::SUCCESS;
        }

        // --- 4) Confirmation ---
        $io->table(
            ['Current roles (stored)', 'New roles (stored)'],
            [[implode(', ', $storedRoles) ?: '—', implode(', ', $newRoles) ?: '—']]
        );

        if (!$io->confirm('Apply these changes?', false)) {
            $io->warning('Operation cancelled.');
            return Command::SUCCESS;
        }

        // --- 5) Persist ---
        $user->setRoles($newRoles);
        $this->em->flush();

        // getRoles() du User rajoute ROLE_USER automatiquement
        $io->success(sprintf(
            'User "%s" promoted to %s. Effective roles now: [%s]',
            $user->getUserIdentifier(),
            $targetRole,
            implode(', ', $user->getRoles())
        ));

        return Command::SUCCESS;
    }

    /**
     * Retourne les rôles tels que stockés en base (sans le ROLE_USER implicite
     * que getRoles() ajoute pour l’authentification).
     */
    private function getStoredRoles(User $user): array
    {
        // getRoles() renvoie roles + ROLE_USER => on le retire pour manipuler la valeur stockée
        $roles = $user->getRoles();
        $roles = array_values(array_diff($roles, ['ROLE_USER']));
        sort($roles);

        return $roles;
    }

    /**
     * Ajoute $role dans $roles si absent.
     */
    private function addRoleIfMissing(array $roles, string $role): array
    {
        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
        }
        sort($roles);

        return $roles;
    }
}
