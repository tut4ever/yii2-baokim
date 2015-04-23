<?php
 /**
 * 
 * bootstrap.php
 *
 * @author Quang Thinh <quangthinh.dico@gmail.com>
 * @link https://github.com/tut4ever/yii2-baokim
 */
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
Yii::setAlias('@tests', __DIR__);
new \yii\console\Application([
    'id' => 'testApp',
    'basePath' => __DIR__
]);