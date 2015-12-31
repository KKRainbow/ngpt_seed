<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-1
 * Time: 上午2:30
 */

use common\library\BEncoder;
use frontend\models\Seed;

$arr = [];
$arr['file'] = [];
/** @var array $seeds */
/** @var Seed $seed */
foreach ($seeds as $seed) {
    $tmp = [
        'complete' => $seed->seeder_count,
        'downloaded' => $seed->completed_count,
        'incomplete' => $seed->leecher_count,
        'name' => $seed->torrent_name,
    ];
    $arr['file'][hex2bin($seed->info_hash)] = $tmp;
}
Yii::info("Response send");
Yii::info($arr);
$be = new BEncoder();
$be->encode($arr);
echo $be->encoded;
