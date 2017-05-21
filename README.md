EncryptionBundle
================

EncryptionBundle is a Symfony bundle whose goal is encrypt the contents of our entities before they are persisted.

### Important! Batch decryption of all the data is not implemented. Once the bundle is enabled and the data is encrypted, there is no way to recover the unencrypted data.
 
There are many alternatives to save the data of your application encrypted, depending on the requirements and constraints.
You can encrypt the partition in which the data is saved using some operative system level encryption, you can encrypt the 
data in the database using some extension of the database management system...

Before you opt for one of them, you should be sure if it's the right one for your requirements and use case.

The EncryptionBundle was originally developed for a determined application with a clear use case in mind. Each user of
the application could store information information that refers to him and that, at least in the first stage of the application,
should not be accessed by other users of the system. The sensitive data should be stored encrypted, and only its owner
should be able to decrypt it. Another requirement to the encryption of the data was that, although initially the data was
to be encrypted and decrypted in the backend, in some moment it should be easy to move the encryption and decryption 
to the client (web browser or mobile app).

# Prerequisites 

This bundle assumes the use of FOSUserBundle 2.x to manage the users of the application, and therefore this should be configured
before enabling the EncryptionBundle. Please refer to the documentation of the bundle to install and configure it.

For the moment the only supported persistence provider is Doctrine, so you should use it to persist the entities that 
should be encrypted.

For the key management and the encryption of the data this bundle uses [openssl](https://www.openssl.org). You should install at least openssl-1.0.2k
in your server. You should also install and enable the [php openssl extension](http://php.net/manual/en/intro.openssl.php) in your server.
Please refer to the installation and configuration instructions for your platform.

# Installation

## Require the bundle

You can install the bundle using composer:

```bash
composer require jagilpe/encryption
```

or add the package to your composer.json file directly.

## Enable the bundle

To enable the bundle, you just have to register the bundle in your AppKernel.php file:

```php
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Jagilpe\EncryptionBundle\JagilpeEncryptionBundle(),
    // ...
);
```

## Create the master encryption key

A master key is required by the bundle to be able to recover the encryption keys of the users in the `per usermode`, ot 
to encrypt and decrypt the data in the `system wide mode`. To create a new master key you can use openssl:

```bash
openssl genrsa -aes256 -out master-key.pem 8192
```

This will create a file called master-key.pem. Copy this file anywhere you want in your server.

## Master key configuration

Edit your config.yml file and include the route to the master key file and the pass phrase you used when you created it. 

```yaml
// app/config.yml
jagilpe_encryption:
                                       master_key:
                                           cert_file: path_to_master_key/master-key.pem
                                           passphrase: key_pass_phrase
```

# Documentation

The encryption is disabled as default. For further instruction about how to enable and use the 
You can access the usage documentation [here](Resources/doc/index.md)

# API Reference

https://api.gilpereda.com/encryption-bundle/master/