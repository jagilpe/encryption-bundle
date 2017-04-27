<?php

namespace Module7\EncryptionBundle\Command;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Module7\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Command class to create the encryption keys for existent users
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class CreateUserKeysCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('jagilpe:encryption:user:generate_keys')
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Input parameters
        $userName = $input->getArgument('usename');
        $allUsers = $input->getOption('all');

        if (!$userName && !$allUsers) {
            throw new \RuntimeException('Wrong parameters given');
        }

        if ($userName && $allUsers) {
            throw new \RuntimeException('Ambiguous parameters given');
        }

        $users = $this->getUsers($userName);

        $total = count($users);
        $message = "Generating the encryption keys for $total users";
        $output->writeln($message);
        $progress = new ProgressBar($output, $total);
        foreach ($users as $user) {
            $this->generateKeys($user);
            $this->saveUser($user);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('');
    }

    private function getUsers($userName)
    {
        $container = $this->getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $encryptionSettings = $container->getParameter('module7_encryption.settings');
        $userClasses = $encryptionSettings['user_classes'];

        $users = array();
        foreach ($userClasses as $userClass) {
            $userRepo = $entityManager->getRepository($userClass);

            if ($userName) {
                $user = $userRepo->findOneBy(array('username' => $userName));
                $users = array($user);
                break;
            }
            else {
                $users = array_merge($users, $userRepo->findAll());
            }
        }

        return $users;
    }

    private function generateKeys(PKEncryptionEnabledUserInterface $user)
    {
        if (!$user->getPublicKey() || !$user->getPrivateKey()) {
            $container = $this->getContainer();
            $keyManager = $container->get('module7_encryption.key_manager');
            $keyManager->generateUserPKIKeys($user);
        }
    }

    private function saveUser(PKEncryptionEnabledUserInterface $user)
    {
        $userClass = ClassUtils::getClass($user);
        $userRepo = $this->getContainer()->get('doctrine')->getManager()->getRepository($userClass);
        $userRepo->save($user);
    }
}