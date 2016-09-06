<?php

namespace EHEncryptionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertMappedFieldsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setName('ehealth_crypt:migrate:encrypt_entities')
            ->setDescription('Converts the unencrypted values of the encrypted enabled field to a compatible form')
        ;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $encryptionService = $container->get('eh_encryption.encryption.service');
        $encryptedEntityTypes = $encryptionService->getEncryptionEnabledEntitiesMetadata();

        foreach ($encryptedEntityTypes as $encryptedEntityType) {
            $repository = $entityManager->getRepository($encryptedEntityType->name);
            $entities = $repository->findAll();
            foreach ($entities as $entity) {
                if (!$entity->isEncrypted()) {
                    $encryptionService->processEntityMigration($entity);
                    $repository->save($entity);
                }
            }
        }
    }
}