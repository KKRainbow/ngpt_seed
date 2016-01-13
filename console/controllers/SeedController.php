<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-13
 * Time: 上午7:43
 */

namespace console\controllers;

use frontend\models\Peer;
use frontend\models\Seed;
use yii\console\Controller;

class SeedController extends Controller
{
    public function actionMaintainCoef()
    {
        $now = time();
        $seeds = Seed::find()
            ->where("coefs_stack[1][3] < {$now}")
            ->andWhere('coefs_stack[1][3] != 0')
            ->all();
        foreach ($seeds as $seed) {
            $arr = $seed->getCoefArray();
            var_dump($arr);
            array_shift($arr);
            //无后备选项
            if (empty($arr)) {
                $arr[] = [100, 100, 0];
            }
            $seed->setCoefArray($arr);
            echo '新系数\n';
            var_dump($seed->getCoefArray());
            $seed->save();
        }
        var_dump(count($seeds));
        return;
    }
}
