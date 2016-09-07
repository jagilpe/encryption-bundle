<?php

namespace Module7\EncryptionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to encrypt the entities that existed before the activation of the encryption
 *
 * @author Javier Gil Pereda <javier.gil@module-7.com>
 */
class EncryptEntitiesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName('m7_crypt:migrate:encrypt_entities')
            ->setDescription('Converts the unencrypted values of the encrypted enabled field to a compatible form')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'If the process should really be executed.'
            )
        ;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');

        if (!$force) {
            throw new \RuntimeException('Include --force option if you really want to proceed');
        }

        $container = $this->getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $encryptionService = $container->get('module7_encryption.encryption.service');

        // Encrypt the entities in the database
        $encryptedEntityTypes = $encryptionService->getEncryptionEnabledEntitiesMetadata();
        foreach ($encryptedEntityTypes as $encryptedEntityType) {
            $entityClass = $encryptedEntityType->name;
            $repository = $entityManager->getRepository($entityClass);
            $entities = $repository->findAll();
            $total = count($entities);
            if ($total > 0){
                $message = "Encrypting $total entities of type $entityClass";
                $output->writeln($message);
                $progress = new ProgressBar($output, $total);
                foreach ($entities as $entity) {
                    if (!$entity->isEncrypted()) {
                        $encryptionService->processEntityMigration($entity);
                        $repository->save($entity);
                    }
                    $progress->advance();
                }

                $progress->finish();
                $output->writeln('');
            }
        }
    }
}