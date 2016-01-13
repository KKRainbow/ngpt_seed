<?php

namespace frontend\models;

use frontend\models\UserQuery;
use Yii;
use yii\behaviors\TimestampBehavior;
use common\models\ActiveRecordTS;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $user_id
 * @property integer $discuz_user_id
 * @property string $passkey
 * @property string $priv
 * @property integer $stat_up
 * @property integer $stat_down
 * @property integer $real_up
 * @property integer $real_down
 * @property integer $extra_up_coef
 * @property integer $extra_down_coef
 * @property string $extra_coef_expire
 * @property boolean $is_valid
 * @property string $create_time
 * @property string $update_time
 *
 * @property History[] $histories
 * @property Peer[] $peers
 * @property Seed[] $publishedSeed
 */
class User extends ActiveRecordTS implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['discuz_user_id', 'passkey'], 'required'],
            [
                [
                    'discuz_user_id', 'stat_up', 'stat_down', 'real_up', 'real_down',
                    'extra_up_coef', 'extra_down_coef',
                ],
                'integer'
            ],
            [['priv'], 'string'],
            [['is_valid'], 'boolean'],
            [['create_time', 'update_time','extra_coef_expire'], 'safe'],
            [['passkey'], 'string', 'max' => 32],
            [['discuz_user_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'discuz_user_id' => 'Discuz User ID',
            'passkey' => 'Passkey',
            'priv' => 'Priv',
            'stat_up' => 'Stat Up',
            'stat_down' => 'Stat Down',
            'real_up' => 'Real Up',
            'real_down' => 'Real Down',
            'is_valid' => 'Is Valid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'extra_up_coef' => 'Extra Up Coef',
            'extra_down_coef' => 'Extra Down Coef',
            'extra_coef_expire' => 'Extra Coef Expire Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHistories()
    {
        return $this->hasMany(History::className(), ['user_id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeers()
    {
        return $this->hasMany(Peer::className(), ['user_id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPublishedSeed()
    {
        return $this->hasMany(Seed::className(), ['publisher_user_id' => 'user_id']);
    }

    /**
     * @inheritdoc
     * @return UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserQuery(get_called_class());
    }

    /**
     * Finds an identity by the given ID.
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['passkey' => $token]);
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string|integer an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        return $this->user_id;
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        //不使用cookie。
        return '';
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return boolean whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        return false;
    }

    public static function genPasskey()
    {
        return strtoupper(md5(time() . rand(1, 10000) . microtime()));
    }

    private function getPeerStat()
    {
        $peers = $this->peers;
        $seeder = 0;
        $leecher = 0;
        //正在下载/做种的种子
        $seed_up = [];
        $seed_down = [];
        foreach ($peers as $p) {
            if ($p->status == 'Seeder') {
                $seeder++;
                $seed_up[$p->seed_id] = 1;
            } else {
                $leecher++;
                $seed_down[$p->seed_id] = 1;
            }
        }
        $ret['seeder_count'] = $seeder;
        $ret['leecher_count'] = $leecher;
        $ret['seed_up_count'] = count(array_keys($seed_up));
        $ret['seed_down_count'] = count(array_keys($seed_down));
        Yii::info($ret);
        return $ret;
    }

    public function getInfo($detail = false)
    {
        $ret = $this->attributes;
        if ($detail) {
            $ret = array_merge($ret, $this->getPeerStat());
            $res = SeedEvent::getUserCounter($this->user_id);
            $ret = array_merge($ret, $res);
            $ret['published_seed'] = count($this->publishedSeed);
        }
        Yii::info($ret);
        return $ret;
    }
}
