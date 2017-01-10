<?php

/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 09.01.2017 15:51
 */

namespace miserenkov\security;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\Json;

class Security extends \yii\base\Security
{
    /**
     * Path to default certificate file
     * @var string
     */
    public $certificateFile;

    /**
     * Path to default public key file
     * @var string
     */
    public $publicKeyFile;

    /**
     * Path to default private key file
     * @var string
     */
    public $privateKeyFile;

    /**
     * Private key passphrase
     * @var string
     */
    public $passphrase = '';

    /**
     * @var resource OpenSSL key
     */
    private $privateKey;

    /**
     * @var resource OpenSSL key
     */
    private $publicKey;

    public function init()
    {
        $this->loadKeys();
        parent::init();
    }

    /**
     * Encrypt $data by public key
     * @param mixed $data
     * @param null|string $publicKey
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function encryptByPublicKey($data, $publicKey = null)
    {
        $publicKey = $this->loadPublicKey($publicKey);
        if (!$publicKey || get_resource_type($publicKey) != 'OpenSSL key') {
            $type = gettype($publicKey);
            $type .= $type == 'resource' ? ('('.get_resource_type($publicKey).')') : '';
            throw new InvalidConfigException("Security::\$publicKey is $type, needed resource(OpenSSL key)");
        }

        if (!is_string($data)) {
            $data = Json::encode($data);
        }

        $encState = openssl_public_encrypt($data, $encrypted, $publicKey);

        if ($encState) {
            return bin2hex($encrypted);
        } else {
            throw new Exception('Encryption error: '.openssl_error_string());
        }
    }

    /**
     * Decrypt $data by private key
     * @param mixed $data
     * @param null|string $privateKey
     * @param string $passphrase
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function decryptByPrivateKey($data, $privateKey = null, $passphrase = '')
    {
        $privateKey = $this->loadPrivateKey($privateKey, $passphrase);
        if (!$privateKey || get_resource_type($privateKey) != 'OpenSSL key') {
            $type = gettype($privateKey);
            $type .= $type == 'resource' ? ('('.get_resource_type($privateKey).')') : '';
            throw new InvalidConfigException("Security::\$privateKey is $type, needed resource(OpenSSL key)");
        }

        $decState = openssl_private_decrypt(hex2bin($data), $decrypted, $privateKey);

        if ($decState) {
            return $decrypted;
        } else {
            throw new Exception('Decryption error: '.openssl_error_string());
        }
    }

    /**
     * Load public key
     * @param $key
     * @return bool|resource
     * @throws InvalidConfigException
     */
    protected function loadPublicKey($key)
    {
        if (($key == $this->publicKeyFile || empty($key)) && $this->publicKey) {
            return $this->publicKey;
        }

        $publicKey = openssl_pkey_get_public($key);
        if (!$publicKey) {
            $alias = Yii::getAlias($key, false);
            $filePath = FileHelper::normalizePath($alias ? $alias : $key);

            if (!file_exists($filePath)) {
                throw new InvalidConfigException('Invalid path to public key file');
            }

            $publicKey = openssl_pkey_get_public(@file_get_contents($filePath));

            if (!$publicKey || get_resource_type($publicKey) != 'OpenSSL key') {
                throw new InvalidConfigException('Can not load public key');
            }
        }

        return $publicKey;
    }

    /**
     * Load private key
     * @param $key
     * @param string $passphrase
     * @return bool|resource
     * @throws InvalidConfigException
     */
    protected function loadPrivateKey($key, $passphrase = '')
    {
        if (($key == $this->privateKeyFile || empty($key)) && $this->privateKey) {
            return $this->privateKey;
        }

        $privateKey = openssl_pkey_get_private($key, $passphrase);

        if (!$privateKey) {
            $alias = Yii::getAlias($key, false);
            $filePath = FileHelper::normalizePath($alias ? $alias : $key);

            if (!file_exists($filePath)) {
                throw new InvalidConfigException('Invalid path to private key file');
            }

            $privateKey = openssl_pkey_get_private(@file_get_contents($filePath), $passphrase);

            if (!$privateKey || get_resource_type($privateKey) != 'OpenSSL key') {
                throw new InvalidConfigException('Can not load private key');
            }
        }

        return $privateKey;
    }

    /**
     * Load public, private keys if defined in settings
     * @throws InvalidConfigException
     */
    protected function loadKeys()
    {
        if ($this->privateKeyFile) {
            $this->privateKey = $this->loadPrivateKey($this->privateKeyFile, $this->passphrase);
        }

        if ($this->publicKeyFile) {
            $this->publicKey = $this->loadPublicKey($this->publicKeyFile);
        } elseif ($this->certificateFile) {
            $this->publicKey = $this->loadPublicKey($this->certificateFile);
        }

        if ($this->publicKey && $this->privateKey) {
            $encryptedText = $this->encryptByPublicKey($this->generateRandomString());

            try {
                $this->decryptByPrivateKey($encryptedText);
            } catch (\Exception $exception) {
                throw new InvalidConfigException('Invalid public-private key pair');
            }
        }
    }
}