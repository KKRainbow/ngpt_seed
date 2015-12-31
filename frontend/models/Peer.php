<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%peer}}".
 *
 * @property integer $peer_id
 * @property integer $user_id
 * @property integer $seed_id
 * @property integer $real_up
 * @property integer $real_down
 * @property string $ipv4_addr
 * @property integer $ipv4_port
 * @property string $ipv6_addr
 * @property integer $ipv6_port
 * @property string $client_tag
 * @property string $status
 * @property string $create_time
 * @property string $update_time
 *
 * @property Seed $seed
 * @property User $user
 */
class Peer extends \common\models\ActiveRecordTS
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%peer}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'seed_id'], 'required'],
            [['user_id', 'seed_id', 'real_up', 'real_down', 'ipv4_port', 'ipv6_port'], 'integer'],
            [['status'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['ipv4_addr'], 'string', 'max' => 17],
            [['ipv6_addr'], 'string', 'max' => 45],
            [['client_tag'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'peer_id' => 'Peer ID',
            'user_id' => 'User ID',
            'seed_id' => 'Seed ID',
            'real_up' => 'Real Up',
            'real_down' => 'Real Down',
            'ipv4_addr' => 'Ipv4 Addr',
            'ipv4_port' => 'Ipv4 Port',
            'ipv6_addr' => 'Ipv6 Addr',
            'ipv6_port' => 'Ipv6 Port',
            'client_tag' => 'Client Tag',
            'status' => 'Status',
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
     * @return PeerQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PeerQuery(get_called_class());
    }
}
