<?php

/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 09.01.2017 15:51
 */

return [
    'id' => 'app-tests',
    'class' => 'yii\console\Application',
    'basePath' => \Yii::getAlias('@tests'),
    'runtimePath' => \Yii::getAlias('@tests/_output'),
    'bootstrap' => [],
    'components' => [
        'security' => [
            'class' => 'miserenkov\security\Security',
            'publicKeyFile' => '@data/pub_key.pem.1',
            'privateKeyFile' => '@data/priv_key.pem.1',
        ],
    ],
];