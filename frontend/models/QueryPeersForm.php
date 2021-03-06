<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 15-12-31
 * Time: 上午12:23
 */

namespace frontend\models;

use Yii;
use yii\base\Model;

/**
 * Class QueryPeersForm
 * @package frontend\models
 * @property Peer $peer 表单对应的peer条目
 * @property Seed $seed 表单对应的peer条目
 */
class QueryPeersForm extends Model
{
    public $passkey;
    public $info_hash;
    public $event;
    public $left;
    public $downloaded;
    public $uploaded;
    public $peer_id; //记住这里的peer_id对应于peer表里的client_tag
    public $numwant;
    public $compact;
    public $no_peer_id = false;

    public $ip;
    public $port;
    public $ipv4 = null;
    public $ipv4port = 0;
    public $ipv6 = null;
    public $ipv6port = 0;

    public $base64_peer_id;

    /**
     * @var Seed $_seed
     */
    private $_seed;
    /**
     * @var Peer $_peer
     */
    private $_peer;

    public function getPeer()
    {
        if (!empty($this->_peer)) {
            return $this->_peer;
        }
        //先看能不能找到，不能找到就新建
        //通过client_tag找
        $peer = Peer::findOne([
            'seed_id' => $this->seed->seed_id,
            'user_id' => Yii::$app->user->identity->getId(),
            'client_tag' => $this->base64_peer_id,
        ]);

        if (empty($peer)) {
            //尝试用ipv4 + ipv6找
            $peer = Peer::findOne([
                'seed_id' => $this->seed->seed_id,
                'user_id' => Yii::$app->user->identity->getId(),
                'ipv4_addr' => $this->ipv4,
                'ipv4_port' => $this->ipv4port,
                'ipv6_addr' => $this->ipv6,
                'ipv6_port' => $this->ipv6port,
            ]);
        }

        //仍然没找到，新建条目
        if (empty($peer)) {
            $peer = new Peer();
            $peer->seed_id = $this->seed->seed_id;
            $peer->user_id = Yii::$app->user->identity->getId();
            //这里不能保存，否则会导致触发器误判
        }
        $peer->ipv4_addr = $this->ipv4;
        $peer->ipv4_port = $this->ipv4port;

        $peer->ipv6_addr = $this->ipv6;
        $peer->ipv6_port = $this->ipv6port;

        $peer->client_tag = $this->base64_peer_id;
        $peer->status = intval($this->left) == 0 ? 'Seeder' : 'Leecher';

        $this->_peer = $peer;
        return $peer;
    }

    public function getSeed()
    {
        return $this->_seed;
    }

    public static function rfc2732Convert($origin, &$addr, &$port)
    {
        $pos = strpos($origin, "]");
        // 2010:836B:4179::836B:4179 这种形式
        if ($pos == false) {
            $addr = $origin;
            $port = null;
        } else {
            $start = strpos($origin, "[");
            $addr = substr($origin, $start + 1, $pos - $start - 1);
            if ($pos == strlen($origin) + 1) {
                $port = null;
            } else {
                //[]:123
                $port = substr($origin, $pos + 2, strlen($origin) - ($pos + 2));
                $port = intval($port);
            }
        }
        return;
    }

    public function beforeValidate()
    {
        $this->info_hash = bin2hex($this->info_hash);
        return parent::beforeValidate();
    }

    public function afterValidate()
    {
        if (!$this->hasErrors()) {
            $this->ipv4 = $this->ip;
            $this->ipv4port = $this->port;
            $ip = null;
            //尝试直接获取ip, 优先采用直接获得的ip
            if (isset($_SERVER['x-forwarded-for'])) {
                $ip = $_SERVER['x-forwarded-for'];
            } elseif (isset($_SERVER['x-forwarded-for'])) {
                $ip = $_SERVER['x-real-ip'];
            } else {
                $ip = Yii::$app->request->userIP;
            }
            if (!empty($ip)) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $this->ipv4 = $ip;
                } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $this->ipv6 = $ip;
                }
            }

            //保证如果为空就设置为null
            if (empty($this->ipv4) || empty($this->ipv4port)) {
                $this->ipv4 = null;
                $this->ipv4port = null;
            }
            if (empty($this->ipv6) || empty($this->ipv6port)) {
                $this->ipv6 = null;
                $this->ipv6port = null;
            }
            if (empty($this->ipv4) && empty($this->ipv6)) {
                $this->addError('ip', "no ip address");
            }

            $this->base64_peer_id = base64_encode($this->peer_id);
        }
        parent::afterValidate(); // TODO: Change the autogenerated stub
    }

    public function rules()
    {
        return [
            [['info_hash', 'peer_id', 'port', 'left', 'downloaded', 'uploaded'],'required'],
            [['passkey', 'info_hash', 'event', 'ip', 'port', 'left', 'no_peer_id',
                'uploaded', 'downloaded', 'ipv6', 'numwant', 'compact'],
                'trim'],
            [['passkey', 'info_hash', 'ip', 'ipv6'], 'default'],
            ['event', 'default', 'value' => 'regular'],
            [['port', 'left', 'uploaded', 'downloaded','compact','numwant']
                , 'default', 'value' => 0],
            [['compact', 'no_peer_id'], 'filter', 'filter' => function ($val) {
                return (bool)$val;
            }],
            [['port', 'left', 'uploaded', 'downloaded', 'numwant']
                , 'integer'],
            ['info_hash', 'string', 'length' => [40, 40]],
            ['peer_id', 'string', 'length' => [4, 30]],
            [['info_hash','passkey'], 'filter', 'filter' => 'strtoupper'],
            ['event', 'filter', 'filter' => 'strtolower'],
            ['ipv6', function ($attr, $params) {
                if (!empty($this->$attr)) {
                    self::rfc2732Convert($this->$attr, $ipv6, $port);
                    if ($port == null) {
                        $port = $this->port;
                    }
                    if (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $port != null) {
                        $this->ipv6 = $ipv6;
                        $this->ipv6port = $port;
                    }
                }
            }],
            ['ip', function ($attr, $param) {
                $this->ipv4 = $this->ip;
                $this->ipv4port = $this->port;
            }],
            ['info_hash', function ($attr, $param) {

                $this->_seed = Seed::findOne([
                    'info_hash' => $this->$attr
                ]);
                if (empty($this->_seed)) {
                    $this->addError('info_hash', 'no such seed');
                } elseif (!$this->_seed->is_valid) {
                    $this->addError('info_hash', 'seed is invalid');
                }
            }],
        ];
    }

}

