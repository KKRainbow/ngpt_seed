<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%seed_operation_record}}".
 *
 * @property integer $record_id
 * @property integer $admin_id
 * @property integer $seed_id
 * @property integer $publisher_id
 * @property string $operation_type
 * @property string $detail_info
 * @property string $create_time
 * @property string $update_time
 *
 * @property Seed $seed
 * @property User $admin
 * @property User $publisher
 */
class SeedOperationRecord extends \common\models\ActiveRecordTS
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%seed_operation_record}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['admin_id', 'seed_id', 'publisher_id'], 'required'],
            [['admin_id', 'seed_id', 'publisher_id'], 'integer'],
            [['detail_info'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['operation_type'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'record_id' => 'Record ID',
            'admin_id' => 'Admin ID',
            'seed_id' => 'Seed ID',
            'publisher_id' => 'Publisher ID',
            'operation_type' => 'Operation Type',
            'detail_info' => 'Detail Info',
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
    public function getAdmin()
    {
        return $this->hasOne(User::className(), ['user_id' => 'admin_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPublisher()
    {
        return $this->hasOne(User::className(), ['user_id' => 'publisher_id']);
    }

    /**
     * @inheritdoc
     * @return SeedOperationRecordQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SeedOperationRecordQuery(get_called_class());
    }
}
