Jagilpe Encryption Bundle Usage Documentation
=======================================

# Master key

Independently if we want to enable or not the encryption, to enable the encryption bundle we have to create a master key pair
using ssl and we have to configure it in the `config.yml`

To create the master key:

```bash
openssl genrsa -aes256 -out master-key.pem 8192
```

To configure it:

```yaml
// app/config.yml
jagilpe_encryption:
    master_key:
        cert_file: path_to_master_key/master-key.pem
        passphrase: key_pass_phrase
```

This master key is used in two cases. For the [System Wide encryption](#System wide encryption) and for recovering the private key 
of the user in case this has forgotten his password in [Per User Encryption](#Per user encryption)

# Enabling the encryption

The encryption is disabled as default. To enable the encryption you should set the `enabled` option to true, the encryption
mode to use as default, and depending on the encryption mode we want to use, configure some other options.

```yaml
jagilpe_encryption:
    enabled: true
    settings:
        default_mode: PER_USER_SHAREABLE
```

The bundle is designed to be as transparent as possible with the logic of the application. It's possible with the same
application code to have the encryption enabled or disabled, and the system should behave the same way, with the exception
that the data will be persisted encrypted or not. 

That does not mean that once the encryption is enabled and we have encrypted data saved in the database, we can disable 
it and have the data clear once again.

ONCE WE HAVE ENABLED THE ENCRYPTION, A GLOBAL DECRYPTION OF ALL THE PERSISTED DATA IS NOT IMPLEMENTED. THE DATA IS ONLY
ACCESSIBLE THROUGH THE APPLICATION BY THE RIGHT USER.

However it's possible to have two systems with the same base code, one with the encryption enabled and one with it disabled.
This can be useful to diagnose if a problem is related or not with the encryption. If it appears in both systems, with
high probability has nothing to do with the encryption.

# Encryption modes

There are two possible modes in which the bundle can work, and its specified in the `jagilpe_encryption.settings.default_mode`
configuration option.

* Per User Encryption
* System wide Encryption

This modes can be simultaneously used in the same application and can be configured for each entity class.

## Per user encryption

In this mode each user has a pair of encryption keys, one public and one private. The public key is used to encrypt the
data of the user, and the private is used to decrypt it.

In this mode the entities with this encryption mode should have a many to one relation with the user entity, so that 
we can say they belong to only one user of the system (more on that below). This user will be the only one able to decrypt 
the data of the entity.

In this mode we should also specify which is the user class of our application and with is the security check route
(route used to authenticate the user). The bundle supports the use of multiple user classes and multiple security check routes, 
for the case that we have multiple authentications in our application.

If we want to use this mode in some of the entities of our application we have to set the `per_user_encryption_enabled` 
option to true (default option)

Per user encryption configuration example

```yaml
jagilpe_encryption:
    enabled: false
    master_key: 
        cert_file: path_to_master_key/master-key.pem
        passphrase: key_pass_phrase
    settings:
        per_user_encryption_enabled: true
        user_classes:
            - "AppBundle\Entity\User"
        security_check_routes:
            - fos_user_security_check
```

If you have no intention to use Per User Encryption in no one of your entities you can disable it setting the `per_user_encryption_enabled` 
option to false. This way you don't have to modify your User Class (see below).

### Enable the Per User Encryption support in the User Class

To support the per user encryption the user class must implement the [Jagilpe\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/PKEncryptionEnabledUserInterface.html)
interface and to use the [Jagilpe\EncryptionBundle\Entity\Traits\EncryptionEnabledUserTrait.php](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/Traits/EncryptionEnabledUserTrait.html)

```php
<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Entity\PKEncryptionEnabledUserInterface;
use Jagilpe\EncryptionBundle\Entity\Traits\EncryptionEnabledUserTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_user")
 */
class User extends BaseUser implements PKEncryptionEnabledUserInterface
{
    use EncryptionEnabledUserTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```
This includes the fields required to store the public and private keys of the user, and to encrypt the private key.

When the user's pair of keys are generated the private key of the user saved clear, but the first time the user changes
his password, this is used to encrypt the private key and save it encrypted in the database. In this moment a copy of the
keys of the user are written in a global key store where the private key is encrypted using the master key of the system.
If the user forgets his password, this copy is used to restore the private key field in the user table.

## System wide encryption

In this mode the data of the entities is encrypted and decrypted using a system key pair (master key). In this case the
data is encrypted using the system's public key, and decrypted using the system's private key. The clear data for this mode
of encryption is accessible through the application. The goal of this mode is therefore protect the data from an
unauthorized access to the database.

No further configuration is required for this encryption mode.

# Configuring an entity for encryption

You can configure the encryption of an entity using annotations directory in the class or using the yaml files (only one 
of them for an entity class).

If you want to use yaml files to configure the encryption of an entity the file should reside in the the directory `Resources/config/m7_encryption`
of the bundle in which the entity is defined, and its name should be the full class name of the entity removing the 
bundle namespace and replacing the slashes with points. For example for the class `AppBundle\Entity\MyEntity` the file 
should have the name `Entity.MyEntity.yml`

The first line of the file should contain the full name of the class.

## Enabling the encryption for an entity

## Enabling the encryption of a field

# Updating the database schema

After enabling and configuring the encryption you have to update the schema of the database, so that the fields required
to support the encryption in the user and in the encryptable entities are generated. You have to do this also if you 
enable the encryption of a new entity or a field of an entity.

Execute in the project's root directory:

```bash
# Symfony 2.x
php app/console doctrine:schema:update --force

# Symfony 3.x
php bin/console doctrine:schema:update --force
```

# Configuration reference

Below you find a reference of all configuration options with their default values if any:

```yaml
jagilpe_encryption:
    enabled: false
    access_checker: "jagilpe_encryption.security.access_checker.chained"
    master_key:
        cert_file: path_to_master_key/master-key.pem
        passphrase: key_pass_phrase
    settings:
        default_mode:
        per_user_encryption_enabled: true
        user_classes: []
        security_check_routes: []
        encrypt_on_backend: true
        decrypt_on_backend: true
        digest_method: SHA256
        symmetric_key_length: 16
        private_key:
            digest_method: SHA512
            bits: 1024
            type: 0  # OPENSSL_KEYTYPE_RSA
        cipher_method:
            property: "AES-128-CBC"
            file: "AES-128-CBC"
            private_key: "AES-256-CBC"
```

