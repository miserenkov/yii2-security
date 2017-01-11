<?php

/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 09.01.2017 15:51
 */

/**
 * Class ComponentTest
 * @property \Codeception\Module\Yii2 $tester
 */
class ComponentTest extends Codeception\Test\Unit
{
    public function testOpenSSLExtension()
    {
        $this->assertTrue(extension_loaded('openssl'));
    }

    public function testValidSecurityComponent()
    {
        $component = Yii::$app->get('security');
        $this->assertInstanceOf('miserenkov\security\Security', $component);
    }

    public function testCorrectEncryptionAndDecryption()
    {
        for ($i = 0; $i < 10; $i++) {
            $originText = Yii::$app->security->generateRandomString(rand(1, 100));

            $encryptedText = Yii::$app->security->encryptByPublicKey($originText);

            $decryptedText = Yii::$app->security->decryptByPrivateKey($encryptedText);

            $this->assertEquals($originText, $decryptedText);
        }
    }

    public function testCorrectEncryptionAndDecryptionInOtherKeyPair()
    {
        for ($i = 0; $i < 5; $i++) {
            $originText = Yii::$app->security->generateRandomString(rand(1, 100));

            $encryptedText = Yii::$app->security->encryptByPublicKey($originText, '@data/pub_key.pem.2');

            $decryptedText = Yii::$app->security->decryptByPrivateKey($encryptedText, '@data/priv_key.pem.2');

            $this->assertEquals($originText, $decryptedText);
        }

        for ($i = 0; $i < 5; $i++) {
            $originText = Yii::$app->security->generateRandomString(rand(1, 100));

            $encryptedText = Yii::$app->security->encryptByPublicKey($originText, '@data/pub_key.pem.3');

            $decryptedText = Yii::$app->security->decryptByPrivateKey($encryptedText, '@data/priv_key.pem.3');

            $this->assertEquals($originText, $decryptedText);
        }
    }

    public function testIncorrectPublicPrivatePair()
    {
        $caught = false;
        try {
            $originText = Yii::$app->security->generateRandomString(rand(1, 100));

            $encryptedText = Yii::$app->security->encryptByPublicKey($originText, '@data/pub_key.pem.1');

            Yii::$app->security->decryptByPrivateKey($encryptedText, '@data/priv_key.pem.2');
        } catch (\yii\base\Exception $e) {
            $caught = true;
        }
        $this->assertTrue($caught, 'Caught exception');
    }

    public function testIsTooLongDataForEncryption()
    {
        $caught = false;
        try {
            Yii::$app->security->encryptByPublicKey(Yii::$app->security->generateRandomString(1000));
        } catch (InvalidArgumentException $exception) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Caught exception');
    }

    public function testHybridEncryption()
    {
        for ($i = 0; $i < 20; $i++) {
            $originText = Yii::$app->security->generateRandomString(rand(100, 1000));

            $encryptedText = Yii::$app->security->encryptHybrid($originText);

            $decryptedText = Yii::$app->security->decryptHybrid($encryptedText);

            $this->assertEquals($originText, $decryptedText);
        }
    }
}