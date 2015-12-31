<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%history}}".
 *
 * @property integer $histroy_id
 * @property integer $user_id
 * @property integer $seed_id
 * @property integer $stat_up
 * @property integer $stat_down
 * @property integer $real_up
 * @property integer $real_down
 * @property string $record_date
 * @property string $create_time
 * @property string $update_time
 *
 * @property Seed $seed
 * @property User $user
 */
class History extends \common\models\ActiveRecordTS
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%history}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'seed_id'], 'required'],
            [['user_id', 'seed_id', 'stat_up', 'stat_down', 'real_up', 'real_down'], 'integer'],
            [['record_date', 'create_time', 'update_time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'histroy_id' => 'Histroy ID',
            'user_id' => 'User ID',
            'seed_id' => 'Seed ID',
            'stat_up' => 'Stat Up',
            'stat_down' => 'Stat Down',
            'real_up' => 'Real Up',
            'real_down' => 'Real Down',
            'record_date' => 'Record Date',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
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

    /**
     * @inheritdoc
     * @return HistoryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new HistoryQuery(get_called_class());
    }
}
