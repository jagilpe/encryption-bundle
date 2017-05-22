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

This master key is used in two cases. For the System Wide encryption and for recovering the private key 
of the user in case this has forgotten his password in Per User Encryption

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
interface and to use the [Jagilpe\EncryptionBundle\Entity\Traits\EncryptionEnabledUserTrait](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/Traits/EncryptionEnabledUserTrait.html)

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

If you want to use yaml files to configure the encryption of an entity the file should reside in the the directory `Resources/config/jgp_encryption`
of the bundle in which the entity is defined, and its name should be the full class name of the entity removing the 
bundle namespace and replacing the slashes with points. For example for the class `AppBundle\Entity\MyEntity` the file 
should have the name `Entity.MyEntity.yml`

The first line of the file should contain the full name of the class.

```yaml
# src/AppBundle/Resources/config/jgp_encryption/Entity.MyEntity.yml
AppBundle\Entity\MyEntity:
    encryptionEnabled: true
```

## Enabling the encryption for an entity

To enable the encryption for an entity you have to set the option `encryptionEnabled` to true. You have also to choose
which encryption mode will have the entity. If you don't specify one, the default mode configured in `app/config/config.yml`
will be used.

### Per User Encryption

If you want to use the per user encryption mode for the entity you have to set the encryption mode to `PER_USER_SHAREABLE`.

Additionally the entity has to implement the [Jagilpe\EncryptionBundle\Entity\PerUserEncryptableEntity](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/PerUserEncryptableEntity.html)
interface and use the [Jagilpe\EncryptionBundle\Entity\Traits\PerUserEncryptableEntityTrait](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/Traits/PerUserEncryptableEntityTrait.html)
trait.

The `PerUserEncryptableEntity` has a method `getOwnerUser` that should return the User the entity belongs to. The `PerUserEncryptableEntityTrait`
trait has a default implementation that assumes the entity has a getter called `getUser` that returns it. If this is not the
case, this method should be overridden with the right implementation.

#### Using Annotations

```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Annotation\EncryptedEntity;
use Jagilpe\EncryptionBundle\Entity\PerUserEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\PerUserEncryptableEntityTrait;

/**
 * @ORM\Entity
 * @EncryptedEntity(enabled=true, mode="PER_USER_SHAREABLE")
 */
class MyEntity implements PerUserEncryptableEntity
{
    use PerUserEncryptableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```

#### Using yaml

```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Entity\PerUserEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\PerUserEncryptableEntityTrait;

/**
 * @ORM\Entity
 */
class MyEntity implements PerUserEncryptableEntity
{
    use PerUserEncryptableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```

```yaml
# src/AppBundle/Resources/config/jgp_encryption/Entity.MyEntity.yml
AppBundle\Entity\MyEntity:
    encryptionEnabled: true
    encryptionMode: "PER_USER_SHAREABLE"
```

#### Override the `getOwnerUser` method

If you want to override the getOwnerUser from the `PerUserEncryptableEntityTrait`, simply rename the method from the trait
and define yours:

```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Entity\PerUserEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\PerUserEncryptableEntityTrait;

/**
 * @ORM\Entity
 */
class MyEntity implements PerUserEncryptableEntity
{
    use PerUserEncryptableEntityTrait {
        getOwnerUser as protected traitGetOwnerUser;
    }
    
    public function getOwnerUser() {
        // Here comes the logic to return the right user object
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```

### System Wide Encryption

If you want to use the system wide encryption mode for the entity you have to set the encryption mode to `SYSTEM_ENCRYPTION`.

Additionally the entity has to implement the [Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/SystemEncryptableEntity.html)
interface and use the [Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/Traits/SystemEncryptableEntityTrait.html)
trait.

#### Using Annotations

```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Annotation\EncryptedEntity;
use Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait;

/**
 * @ORM\Entity
 * @EncryptedEntity(enabled=true, mode="SYSTEM_ENCRYPTION")
 */
class MyEntity implements SystemEncryptableEntity
{
    use SystemEncryptableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```

#### Using yaml

```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait;

/**
 * @ORM\Entity
 */
class MyEntity implements SystemEncryptableEntity
{
    use SystemEncryptableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
```

```yaml
# src/AppBundle/Resources/config/jgp_encryption/Entity.MyEntity.yml
AppBundle\Entity\MyEntity:
    encryptionEnabled: true
    encryptionMode: "SYSTEM_ENCRYPTION"
```

## Enabling the encryption of a field

The encryption of the data is made in a per field basis. Once you have enabled the encryption for the entity you can 
specify which fields have to be encrypted. There are although two restrictions:
 
 * You can not encrypt the Id field, or any reference to other entities. In general you should only encrypt the data
 that is really sensitive from the point of view of the logic of the application.
 * Once you have enabled the encryption for an entity you can not activate the encryption for a field that already has
 data persisted in the database. However if you add a new field to an entity, you can activate the encryption for it, 
 because the already existing register for the entity have no value for the new field.
 
To activate the encryption for a field of the entity, simply add the `Jagilpe\EncryptionBundle\Annotation\EncryptedField`
annotation to the property definition, or include it in the `encryptedFields` entry of the yml file

#### Supported column types

The currently supported doctrine column types for the encrypted fields are:

* string
* text
* boolean
* smallint
* integer
* bigint
* float
* date
* datetime
* time
* json_array
* simple_array
* object

#### Using annotations

```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Annotation\EncryptedEntity;
use Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait;
use Jagilpe\EncryptionBundle\Annotation\EncryptedField;

/**
 * @ORM\Entity
 * @EncryptedEntity(enabled=true, mode="SYSTEM_ENCRYPTION")
 */
class MyEntity implements SystemEncryptableEntity
{
    use SystemEncryptableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string")
     * @EncryptedField
     */
    protected $myEncryptedField;
    
    public function getMyEncryptedField()
    {
        return $this->myEncryptedField;
    }
        
    public function setMyEncryptedField($myEncryptedField)
    {
        $this->myEncryptedField = $myEncryptedField;
        return $this;
    }
}
```

#### Using yaml


```php
<?php
// src/AppBundle/Entity/MyEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait;

/**
 * @ORM\Entity
 */
class MyEntity implements SystemEncryptableEntity
{
    use SystemEncryptableEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string")
     */
    protected $myEncryptedField;
    
    public function getMyEncryptedField()
    {
        return $this->myEncryptedField;
    }
        
    public function setMyEncryptedField($myEncryptedField)
    {
        $this->myEncryptedField = $myEncryptedField;
        return $this;
    }
}
```

```yaml
# src/AppBundle/Resources/config/jgp_encryption/Entity.MyEntity.yml
AppBundle\Entity\MyEntity:
    encryptionEnabled: true
    encryptionMode: "SYSTEM_ENCRYPTION"
    encryptedFields:
        myEncryptedField: ~
```

# Encrypting file entities

One of the requirements of the original application this bundle was built for was that the user should be able to upload files
and later access them through the web frontend and using a mobile client. This files could contain sensitive information
and therefore had to be also encrypted before they were saved in the filesystem.
 
The Encryption Bundle supports the encryption and decryption of this files, and although this functionality is heavily 
dependent on how the storage of this files was implemented, it's general enough to be used in other applications.

## The file entity

In order to be able to encrypt the files used in the application they have to be managed through a file entity. This entity
has to implement the [Jagilpe\EncryptionBundle\Entity\EncryptableFile](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/EncryptableFile.html)
interface, and use the [Jagilpe\EncryptionBundle\Entity\Traits\EncryptableFileTrait](https://api.gilpereda.com/encryption-bundle/master/Jagilpe/EncryptionBundle/Entity/Traits/EncryptableFileTrait.html)
trait.

## Configuration of the file encryption

In order to activate the encryption of the file associated with a file entity, this has to have the entity encryption enabled.
After that you can activate the file encryption using the `Jagilpe\EncryptionBundle\Annotation\EncryptedFile` annotation
or the `encryptedFile` in the yaml configuration file

#### Using annotations

```php
<?php
// src/AppBundle/Entity/MyFileEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Annotation\EncryptedEntity;
use Jagilpe\EncryptionBundle\Annotation\EncryptedFile;
use Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\EncryptableFile;
use Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait;
use Jagilpe\EncryptionBundle\Entity\Traits\EncryptableFileTrait;

/**
 * @ORM\Entity
 * @EncryptedEntity(enabled=true, mode="SYSTEM_ENCRYPTION")
 * @EncryptedFile
 */
class MyFileEntity implements SystemEncryptableEntity, EncryptableFile
{
    use SystemEncryptableEntityTrait;
    
    use EncryptableFileTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContent() {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function setContent($content) {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAbsolutePath() {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function fileExists() {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFile() {
        // Your logic here
    }
}
```

#### Using yaml

```php
<?php
// src/AppBundle/Entity/MyFileEntity.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jagilpe\EncryptionBundle\Entity\SystemEncryptableEntity;
use Jagilpe\EncryptionBundle\Entity\EncryptableFile;
use Jagilpe\EncryptionBundle\Entity\Traits\SystemEncryptableEntityTrait;
use Jagilpe\EncryptionBundle\Entity\Traits\EncryptableFileTrait;

/**
 * @ORM\Entity
 */
class MyFileEntity implements SystemEncryptableEntity, EncryptableFile
{
    use SystemEncryptableEntityTrait;
    
    use EncryptableFileTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContent() {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function setContent($content) {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAbsolutePath() {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function fileExists() {
        // Your logic here
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFile() {
        // Your logic here
    }
}
```

```yaml
# src/AppBundle/Resources/config/jgp_encryption/Entity.MyFileEntity.yml
AppBundle\Entity\MyEntity:
    encryptionEnabled: true
    encryptedFile: true
    encryptionMode: "SYSTEM_ENCRYPTION"
    encryptedFields:
        myEncryptedField: ~
```

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

# Initial encryption of existing data

You can install and activate the EncryptionBundle in a system that already has data. The bundle comes with two commands 
that help with the migration of the existing data.

## Generation of the encryption keys for the existing users

If you are using Per User Encryption in your application, each user has to have a pair of keys. To generate the keys for 
the users that existed before the activation of the bundle, simply run the following command in your project's root:

```bash
# Symfony 2
php app/console jagilpe:encryption:user:generate_keys --all
# Symfony 3
php bin/console jagilpe:encryption:user:generate_keys --all
```

## Encryption of the already existing data

To encrypt the data that already existed before the activation of the bundle, simply run the following command in your project's root:

```bash
# Symfony 2
php app/console jagilpe:encryption:migrate:encrypt_entities --force
# Symfony 3
php bin/console jagilpe:encryption:migrate:encrypt_entities --force
```

## Encryption of the existing data

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