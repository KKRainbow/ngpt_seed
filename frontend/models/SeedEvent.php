<?php

namespace frontend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%seed_event}}".
 *
 * @property integer $record_id
 * @property integer $seed_id
 * @property integer $user_id
 * @property string $event_type
 * @property string $create_time
 *
 * @property Seed $seed
 * @property User $user
 */
class SeedEvent extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%seed_event}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['seed_id', 'user_id', 'event_type'], 'required'],
            [['seed_id', 'user_id'], 'integer'],
            [['event_type'], 'string'],
            [['create_time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'record_id' => 'Record ID',
            'seed_id' => 'Seed ID',
            'user_id' => 'User ID',
            'event_type' => 'Event Type',
            'create_time' => 'Create Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeed()
    {
        return $this->hasOne(Seed::className(), ['seed_id' => 'seed_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['user_id' => 'user_id']);
    }

    public static function getUserCounter($user_id)
    {
        $tbl = static::tableName();
        $seed_count_sql = <<<SQL
SELECT
SUM(CASE event_type WHEN 'Downloaded' THEN 1 ELSE 0 END) AS downloaded_count,
SUM(CASE event_type WHEN 'Completed' THEN 1 ELSE 0 END) AS completed_count
FROM  {$tbl} WHERE user_id=$user_id;
SQL;
        $res = Yii::$app->db->createCommand($seed_count_sql)->queryOne();
        if (empty($res['downloaded_count'])) {
            $res['downloaded_count'] = 0;
        }
        if (empty($res['completed_count'])) {
            $res['completed_count'] = 0;
        }
        Yii::info($res);
        return $res;
    }
}
