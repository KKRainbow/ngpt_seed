<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%seed}}".
 *
 * @property integer $seed_id
 * @property integer $type_id
 * @property integer $sub_type_id
 * @property string $info_hash
 * @property string $source_str
 * @property string $torrent_name
 * @property string $full_name
 * @property integer $file_size
 * @property integer $file_count
 * @property integer $publisher_user_id
 * @property integer $seeder_count
 * @property integer $leecher_count
 * @property integer $completed_count
 * @property string $last_active_time
 * @property boolean $is_valid
 * @property string $pub_time
 * @property integer $traffic_up
 * @property integer $traffic_down
 * @property string $coefs_stack
 * @property integer $live_time
 * @property string $detail_info
 * @property string $create_time
 * @property string $update_time
 *
 * @property array $coefArray
 *
 * @property History[] $histories
 * @property Peer[] $peers
 * @property User $publisher
 */
class Seed extends \common\models\ActiveRecordTS
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%seed}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['info_hash'], 'required'],
            [
                ['file_size', 'file_count', 'seeder_count',
                'leecher_count', 'completed_count',
                'traffic_up', 'traffic_down', 'live_time',
                'publisher_user_id', 'type_id', 'sub_type_id'],
                'integer'
            ],
            [['last_active_time', 'pub_time', 'create_time', 'update_time'], 'safe'],
            [['is_valid'], 'boolean'],
            [['coefs_stack', 'detail_info', 'full_name'], 'string'],
            [['info_hash'], 'string', 'max' => 40],
            [['source_str'], 'string', 'max' => 50],
            [['torrent_name'], 'string', 'max' => 250],
            [['info_hash'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'seed_id' => 'Seed ID',
            'type_id' => 'Type ID',
            'sub_type_id' => 'Sub Type ID',
            'info_hash' => 'Info Hash',
            'source_str' => 'Source Str',
            'torrent_name' => 'Torrent Name',
            'full_name' => 'Full Name',
            'file_size' => 'File Size',
            'file_count' => 'File Count',
            'seeder_count' => 'Seeder Count',
            'leecher_count' => 'Leecher Count',
            'completed_count' => 'Completed Count',
            'last_active_time' => 'Last Active Time',
            'is_valid' => 'Is Valid',
            'pub_time' => 'Pub Time',
            'traffic_up' => 'Traffic Up',
            'traffic_down' => 'Traffic Down',
            'coefs_stack' => 'Coefs Stack',
            'live_time' => 'Live Time',
            'detail_info' => 'Detail Info',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'publisher_user_id' => 'Publisher User ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHistories()
    {
        return $this->hasMany(History::className(), ['seed_id' => 'seed_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeers()
    {
        return $this->hasMany(Peer::className(), ['seed_id' => 'seed_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPublisher()
    {
        return $this->hasOne(User::className(), ['user_id' => 'publisher_user_id']);
    }

    /**
     * @inheritdoc
     * @return SeedQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SeedQuery(get_called_class());
    }

    public function setCoefArray($val)
    {
        $tmp = $val;
        $tmp = json_encode($tmp);
        $tmp = preg_replace('/\\[/', '{', $tmp);
        $tmp = preg_replace('/\\]/', '}', $tmp);
        $this->coefs_stack = $tmp;
        Yii::info("set coef stack : ". $tmp, static::className());
    }

    public function getCoefArray()
    {
        $tmp = $this->coefs_stack;
        $tmp = preg_replace('/{/', '[', $tmp);
        $tmp = preg_replace('/}/', ']', $tmp);
        if (empty($tmp)) {
            Yii::warning("coef is empty", static::className());
        }
        $ret = json_decode($tmp, true);
        Yii::info("get coef stack : ". print_r($ret, true), static::className());
        return $ret;
    }
}
