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

    /**
     * @var int
     */
    private $dataMaxLength;

    /**
     * @var array
     */
    private $paddingLengths = [
        OPENSSL_PKCS1_PADDING => 11,
        OPENSSL_SSLV23_PADDING => 11,
        OPENSSL_NO_PADDING => 0,
        OPENSSL_PKCS1_OAEP_PADDING => 42,
    ];

    /**
     * OpenSSL padding
     * @var int
     */
    private $padding = OPENSSL_PKCS1_PADDING;

    public function init()
    {
        $this->loadKeys();
        parent::init();
    }

    /**
     * Encrypt $data by public key
     * @param string $data
     * @param null|string $publicKey
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \InvalidArgumentException
     */
    public function encryptByPublicKey($data, $publicKey = null)
    {
        $publicKey = $this->loadPublicKey($publicKey);
        if (!$publicKey || get_resource_type($publicKey) != 'OpenSSL key') {
            $type = gettype($publicKey);
            $type .= $type == 'resource' ? ('('.get_resource_type($publicKey).')') : '';
            throw new InvalidConfigException("Security::\$publicKey is $type, needed resource(OpenSSL key)");
        }

        if (!is_string($data) && !is_numeric($data)) {
            throw new \InvalidArgumentException('Data for encryption must be a string, found '.gettype($data));
        }

        $maxDataLength = $this->getDataMaxLength($publicKey);
        if (strlen($data) > $maxDataLength) {
            throw new \InvalidArgumentException("Data for encryption is too long, must be maximum $maxDataLength symbols.");
        }

        $encState = openssl_public_encrypt($data, $encrypted, $publicKey, $this->padding);

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
            $this->dataMaxLength = $this->getDataMaxLength($this->privateKey);
        }

        if ($this->publicKeyFile) {
            $this->publicKey = $this->loadPublicKey($this->publicKeyFile);
        } elseif ($this->certificateFile) {
            $this->publicKey = $this->loadPublicKey($this->certificateFile);
        }

        if ($this->publicKey && !$this->dataMaxLength) {
            $this->dataMaxLength = $this->getDataMaxLength($this->publicKey);
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

    /**
     * Get maximum data length with padding
     * @param $key
     * @return int
     */
    protected function getDataMaxLength($key)
    {
        if (gettype($key) === 'resource') {
            $key_details = openssl_pkey_get_details($key);
            $data_length = strlen($key_details['rsa']['n']);

            return $data_length - $this->paddingLengths[$this->padding];
        }

        return 0;
    }
}