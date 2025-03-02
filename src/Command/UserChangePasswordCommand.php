<?php

namespace App\Command;

use App\Manager\UserManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserChangePasswordCommand extends Command
{
    protected static $defaultName = 'app:user:change-password';
    protected static $defaultDescription = "Changes a user's password";

    private UserManager $userManager;

    public function __construct(string $name = null, UserManager $userManager)
    {
        $this->userManager = $userManager;

        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user who you want to change the password for')
            ->addArgument('password', InputArgument::REQUIRED, 'The new password')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $io = new SymfonyStyle($input, $output);

        if (is_null($input->getArgument('username'))) {
            $username = $io->ask('Username');
            $input->setArgument('username', $username);
        }

        if (is_null($input->getArgument('password'))) {
            $password = $io->askHidden('password');
            $input->setArgument('password', $password);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        try {
            $this->userManager->changePassword($username, $password);
        } catch (NotFoundHttpException $exception) {
            $io->error("User not found for username '$username'");
            return Command::FAILURE;
        }

        $io->success("Password has been changed successfully");

        return Command::SUCCESS;
    }
}
