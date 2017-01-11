# Yii2 Security extension
Yii2 extension for encryption and decryption by openssl public/private keys

[![License](https://poser.pugx.org/miserenkov/yii2-security/license)](https://packagist.org/packages/miserenkov/yii2-security)
[![Latest Stable Version](https://poser.pugx.org/miserenkov/yii2-security/v/stable)](https://packagist.org/packages/miserenkov/yii2-security)
[![Latest Unstable Version](https://poser.pugx.org/miserenkov/yii2-security/v/unstable)](https://packagist.org/packages/miserenkov/yii2-security)
[![Total Downloads](https://poser.pugx.org/miserenkov/yii2-security/downloads)](https://packagist.org/packages/miserenkov/yii2-security)
[![Build Status](https://travis-ci.org/miserenkov/yii2-security.svg?branch=master)](https://travis-ci.org/miserenkov/yii2-security)
[![Dependency Status](https://www.versioneye.com/user/projects/5876351c9fb7130049911798/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/5876351c9fb7130049911798)
<!--[![HHVM Status](http://hhvm.h4cc.de/badge/miserenkov/yii2-security.svg)](http://hhvm.h4cc.de/package/miserenkov/yii2-security)-->

## Support

[GitHub issues](https://github.com/miserenkov/yii2-security).

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist miserenkov/yii2-security "^1.0"
```

or add

```
"miserenkov/yii2-security": "^1.0"
```

to the require section of your `composer.json` file.

## Configuration

To use security extension, you should configure it in the application configuration like the following
```php
'components' => [
    ...
    'security' => [
        'class' => 'miserenkov\security\Security',
        
        'certificateFile' => '',    // alias or path to default certificate file
        // or
        'publicKeyFile' => '',      // alias or path to default public key file
        
        'privateKeyFile' => '',     // alias or path to default private key file
        'passphrase' => '',         // passphrase to default private key (if exists)
    ],
    ...
],
```

# Basic usages

## Encryption
### Asymmetric encryption
Will return base64 encoded encrypted data.
###### With default public key
```php
Yii::$app->security->encryptByPublicKey(
    $data           // string data for ecnryption
);
```

###### With custom public key
```php
Yii::$app->security->encryptByPublicKey(
    $data,          // string data for ecnryption
    $publicKey      // alias or path to custom public key or PEM formatted public key
);
```

### Hybrid encryption
Will return array from encryption key and encrypted data \(\['key' => '...', 'data' => '...']).
###### With default public key
```php
Yii::$app->security->encryptHybrid(
    $data           // string data for ecnryption
);
```

###### With custom public key
```php
Yii::$app->security->encryptHybrid(
    $data,          // string data for ecnryption
    $publicKey      // alias or path to custom public key or PEM formatted public key
);
```

## Decryption
### Asymmetric decryption
Will return decrypted data.
###### With default private key
```php
Yii::$app->security->decryptByPrivateKey(
    $data           // string data for decryption
);
```

###### With custom private key
```php
Yii::$app->security->decryptByPrivateKey(
    $data,          // string data for decryption
    $privateKey,    // alias or path to custom private key or PEM formatted private key
    $passphrase     // passphrase for private key (if exists)
);
```

### Hybrid encryption
Will return decrypted data.
###### With default private key
```php
Yii::$app->security->decryptHybrid(
    $data,          // string data for decryption or array from 
                    // encryption key and ecnrypted data (['key' => '...', 'data' => '...'])
    $key            // encryption key if $data as string
);
```

###### With custom private key
```php
Yii::$app->security->decryptHybrid(
    $data,          //string data for decryption or array from 
                    // encryption key and ecnrypted data (['key' => '...', 'data' => '...'])
    $key,           // encryption key if $data as string
    $privateKey,    // alias or path to custom private key or PEM formatted private key
    $passphrase     // passphrase for private key (if exists)
);
```