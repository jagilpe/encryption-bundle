<?php

namespace Module7\EncryptionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\User;
use Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Command class to create the encryption keys for existent users
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class CreateUserKeysCommand extends ContainerAwareCommand
{
    const APP_VIVA = 'viva';
    const APP_CONNECT = 'connect';

    private static $apps = array(
        self::APP_VIVA,
        self::APP_CONNECT,
    );

    protected function configure()
    {
        $this->setName('m7_crypt:user:generate_keys')
            ->setDescription('Generates the encryption keys of a user')
            ->addArgument(
                'usename',
                InputArgument::OPTIONAL,
                'The name of the user whose keys we want to create.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'If the keys of all users should be generated.'
            )
            ->addOption(
                'app',
                null,
                InputOption::VALUE_OPTIONAL,
                'The application in which to look the user up. It can be Viva or Connect',
                self::APP_VIVA
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Input parameters
        $userName = $input->getArgument('usename');
        $allUsers = $input->getOption('all');
        $app = $input->getOption('app');

        if (!$userName && !$allUsers) {
            throw new \RuntimeException('Wrong parameters given');
        }

        if ($userName && $allUsers) {
            throw new \RuntimeException('Ambiguous parameters given');
        }

        if (!in_array(strtolower($app), self::$apps)) {
            throw new \RuntimeException('Wrong application selected');
        }

        $users = $this->getUsers($userName, $app);

        $total = count($users);
        $message = "Generating the encryption keys for $total users";
        $output->writeln($message);
        $progress = new ProgressBar($output, $total);
        foreach ($users as $user) {
            $this->generateKeys($user, $app);
            $this->saveUser($user, $app);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');
    }

    private function getUsers($userName, $app)
    {
        switch ($app) {
            case self::APP_VIVA:
                $em = $this->getContainer()->get('doctrine')->getManager();
                $userRepo = $em->getRepository('AppBundle:User');
                if ($userName) {
                    $user = $userRepo->findOneBy(array('username' => $userName));
                    if (!$user) {
                        throw new \RuntimeException('User not found');
                    }

                    $users = array($user);
                }
                else {
                    $users = $userRepo->findAll();
                }
                break;
            case self::APP_CONNECT:
                $userManager = $this->getContainer()->get('polavis_connect.user_manager');
                if ($userName) {
                    $user = $userManager->findUserByUsername($userName);
                    if (!$user) {
                        throw new \RuntimeException('User not found');
                    }

                    $users = array($user);
                }
                else {
                    $users = $userManager->findUsersBy(array());
                }
                break;
        }

        return $users;
    }

    private function generateKeys(PKEncryptionEnabledUserInterface $user)
    {
        if (!$user->getPublicKey() || !$user->getPrivateKey()) {
            $container = $this->getContainer();
            $keyManager = $container->get('eh_encryption.key_manager');
            $keyManager->generateUserPKIKeys($user);
        }
    }

    private function saveUser(PKEncryptionEnabledUserInterface $user, $app)
    {
        switch ($app) {
            case self::APP_VIVA:
                $em = $this->getContainer()->get('doctrine')->getManager();
                $userRepo = $em->getRepository('AppBundle:User');
                $userRepo->save($user);
                break;
            case self::APP_CONNECT:
                $userManager = $this->getContainer()->get('polavis_connect.user_manager');
                $userManager->updateUser($user);
                break;
        }
    }
}