<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%peer_lifecycle}}".
 *
 * @property integer $record_id
 * @property integer $user_id
 * @property integer $seed_id
 * @property string $ipv4
 * @property string $ipv6
 * @property string $status
 * @property string $client_tag
 * @property string $begin_time
 * @property string $end_time
 *
 * @property Peer[] $peers
 * @property Seed $seed
 * @property User $user
 */
class PeerLifecycle extends \common\models\ActiveRecordTS
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%peer_lifecycle}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'seed_id', 'status'], 'required'],
            [['user_id', 'seed_id'], 'integer'],
            [['ipv4', 'ipv6', 'status'], 'string'],
            [['begin_time', 'end_time'], 'safe'],
            [['client_tag'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'record_id' => 'Record ID',
            'user_id' => 'User ID',
            'seed_id' => 'Seed ID',
            'ipv4' => 'Ipv4',
            'ipv6' => 'Ipv6',
            'status' => 'Status',
            'client_tag' => 'Client Tag',
            'begin_time' => 'Begin Time',
            'end_time' => 'End Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeers()
    {
        return $this->hasMany(Peer::className(), ['lifecycle_id' => 'record_id']);
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
}
