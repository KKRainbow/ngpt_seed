<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 15-12-31
 * Time: 下午10:31
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

class ActiveRecordTS extends ActiveRecord
{
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT =>
                        ['create_time', 'update_time'],
                    ActiveRecord::EVENT_BEFORE_UPDATE =>
                        ['update_time'],
                ],
                'value' => new Expression('now()'),
            ]
        ];
    }
}