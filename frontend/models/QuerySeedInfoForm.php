<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-2
 * Time: 下午7:12
 */

namespace frontend\models;

use Yii;
use yii\base\Model;

class QuerySeedInfoForm extends Model
{
    /**
     * {
     *      seed_id : index_id,
     *      seed_id : index_id,
     *      ....
     * }
     */
    public $query_json;
    public function rules()
    {
        return [
            ['query_json', 'required'] ,
        ];
    }
    public function getSeedInfos()
    {
        $arr = json_decode($this->query_json, true);
        if (empty($arr)) {
            return false;
        }
        $ids = array_keys($arr);
        $seedRes = Seed::findAll($ids);

        $ret = [];
        foreach ($seedRes as $seed) {
            $id = $arr[$seed['seed_id']];
            $ret[$id] = $seed->attributes;
            // 格式：{{100,100,50},{100,50,50}}
            //第一个是up，第二个是down
            $coef = $seed->coefArray;
            $ret[$id]['up_coef'] = $coef[0][0];
            $ret[$id]['down_coef'] = $coef[0][1];
            $expire = intval($coef[0][2]);
            Yii::info($expire);
            $ret[$id]['coef_expire_time'] = $expire == 0 ? 0 : $expire - time();
        }
        return $ret;
    }
}
