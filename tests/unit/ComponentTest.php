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
}