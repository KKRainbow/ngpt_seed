<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-1
 * Time: 上午3:40
 */

namespace frontend\controllers;

use common\library\TorrentFileList;
use common\library\TorrentFileTool;
use frontend\models\History;
use frontend\models\Peer;
use frontend\models\SeedEvent;
use frontend\models\SeedOperationRecord;
use frontend\models\SeedOperationRecordQuery;
use frontend\models\SeedSearchForm;
use frontend\models\User;
use yii\web\Controller;
use frontend\models\Seed;
use frontend\models\UploadedSeedFile;
use Yii;
use yii\filters\auth\QueryParamAuth;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\ContentNegotiator;
use frontend\models\QuerySeedInfoForm;

class SeedController extends Controller
{
    public $enableCsrfValidation = false;
    public function behaviors()
    {
        $behaviors = parent::behaviors(); // TODO: Change the autogenerated stub
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className(),
            'tokenParam' => 'passkey',
        ];
        $behaviors[] = [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                    'text/html' => Response::FORMAT_HTML,
                ],
        ];
        return $behaviors;
    }

    public function actionUpload()
    {
        $seedModel = new UploadedSeedFile();
        if (Yii::$app->request->isPost) {
            $response = [];
            $seedModel->torrentFile = UploadedFile::getInstanceByName('torrentFile');
            $seedModel->attributes = Yii::$app->request->post();
            Yii::info($seedModel->torrentFile);
            $retval = null;
            $res = $seedModel->upload($retval);
            Yii::info($retval);
            $response['result'] = $retval;
            if ($retval == 'succeed') {
                $response['extra'] = $res->attributes;
            } elseif ($retval == 'exists') {
                $response['extra'] = $res->seed_id;
            } else {
                $response['extra'] = $seedModel->errors;
            }
        } else {
            $response['result'] = 'failed';
            $response['extra'] = 'method not post';
        }
        Yii::info($response);
        return $response;
    }

    public function actionInfo()
    {
        $model = new QuerySeedInfoForm();
        $model->attributes = Yii::$app->request->post();
        $ret = $model->getSeedInfos();
        $result = [];
        if (empty($ret)) {
            $result['result'] = 'failed';
        } else {
            $result['result'] = 'succeed';
            $result['extra'] = $ret;
        }
        Yii::info($result);
        return $result;
    }

    public function actionDownload($seed_id)
    {
        $seed = Seed::findOne($seed_id);
        if (empty($seed)) {
            return [
                'result' => 'failed',
                'extra' => 'no such seed',
            ];
        }

        $main_tracker = "http://10.254.36.2:8080/announce.php?";
        //鉴于announce_list实际上是扩展，经过实验，如果有announce_list，
        //announce就不再起作用，所以把main_tracker放到list的第一个
        $secondary_tracker = [
            $main_tracker,
            "http://10.254.36.2:9001/announce.php?",
            "http://tracker1.bevip.xyz:8081/announce.php?",
            "http://tracker2.bevip.xyz:8082/announce.php?",
            "http://tracker3.bevip.xyz:8083/announce.php?",
            "http://tracker4.bevip.xyz:8084/announce.php?"
        ];


        $info_path = getcwd() . "/torrents/" . $seed['info_hash'] . '.info';

        $torrent = TorrentFileTool::buildTorrentFile(
            $info_path,
            $main_tracker,
            $secondary_tracker
        );
        $event = new SeedEvent();
        $event->seed_id = $seed_id;
        $event->user_id = Yii::$app->user->identity->getId();
        $event->event_type = 'Downloaded';
        $event->insert();
        return Yii::$app->response->sendContentAsFile(
            $torrent,
            '[NGPT]' . $seed['torrent_name'] . '.torrent'
        );
    }

    public function actionGetFormJson($fid)
    {
        $cache = Yii::$app->cache;
        $key = static::className() . 'Form;fid:' . $fid;
        $ret = $cache->get($key);
        if ($ret === false) {
            $ret = [];
            $ret['json'] = UploadedSeedFile::getMetaInfo($fid);
            $ret['result'] = empty($ret['json']) ? 'failed' : 'succeed';
            $cache->set($key, $ret, 120);
        }
        return $ret;
    }

    public function actionDelete($seed_id, $reason = "", $penalty = 0)
    {
        if (!is_numeric($penalty) || !is_numeric($seed_id)) {
            return [
                'result' => 'failed',
                'extra' => 'illegal param'
            ];
        }
        $penalty = intval($penalty);
        $seed_id = intval($seed_id);
        /** @var User $user */
        $user = User::findOne(Yii::$app->user->identity->getId());
        if ($user->priv != 'Admin') {
            return [
                'result' => 'failed',
                'extra' => 'permission denied'
            ];
        }
        /** @var Seed $seed */
        $seed = Seed::findOne($seed_id);
        if (empty($seed) || !$seed->is_valid) {
            return [
                'result' => 'failed',
                'extra' => 'not exists'
            ];
        }
        $ret['result'] = 'success';
        $publisher = $seed->publisher;
        $record = new SeedOperationRecord();
        $record->admin_id = $user->user_id;
        $record->publisher_id = $publisher->user_id;
        $record->seed_id = $seed->seed_id;
        $record->operation_type = "DELETE";
        $record->detail_info = json_encode([
            'penalty' => $penalty,
            'reason' => $reason
        ]);
        $record->insert();
        $seed->is_valid = false;
        $seed->save();
        $publisher->stat_up -= intval($penalty) * (1<<30);
        $publisher->save();
        $tmp = $seed->attributes;
        $tmp['discuz_pub_uid'] = $publisher->discuz_user_id;
        //查询所有下载过这个种子的人
        $res = History::find()
            ->leftJoin(
                User::tableName(),
                User::tableName() . '.user_id=' . History::tableName() . ".user_id"
            )
            ->where([
            'seed_id' => $seed->seed_id
            ])->groupBy([History::tableName() . ".user_id", "history_id"])->all();
        Yii::info($res);
        foreach ($res as $r) {
            $tmp['discuz_use_uid'][] = $r->user->discuz_user_id;
        }
        $ret['extra'] = $tmp;
        Yii::info($ret);
        return $ret;
    }

    /**
     * @param int $seed_id
     * @param int $upcoe
     * @param int $downcoe
     * @param int $duration 该系数的持续时间,后面会转换成到期时间,0表示永久
     * @param string $reason
     * @return array
     * @throws \Exception
     */
    public function actionSetCoef($seed_id, $upcoe, $downcoe, $duration, $reason, $replace)
    {
        $ret = [];
        $ret['result'] = 'failed';
        if (!is_numeric($upcoe) || !is_numeric($downcoe) ||
            !is_numeric($duration) || !is_numeric($seed_id)) {
            $ret['extra'] = 'permission denied';
            return $ret;
        }
        /** @var User $user */
        $user = User::findOne(Yii::$app->user->identity->getId());
        if ($user->priv != 'Admin') {
            $ret['extra'] = 'permission denied';
            return $ret;
        }

        /** @var Seed $seed */
        $seed = Seed::findOne($seed_id);
        if (empty($seed) || !$seed->is_valid) {
            $ret['extra'] = 'not exists';
            return $ret;
        }

        $ret['result'] = 'success';
        $publisher = $seed->publisher;
        $record = new SeedOperationRecord();
        $record->admin_id = $user->user_id;
        $record->publisher_id = $publisher->user_id;
        $record->seed_id = $seed->seed_id;
        $record->operation_type = "SETCOEF";
        $record->detail_info = json_encode([
            'reason' => $reason,
            'up_coe' => $upcoe,
            'down_coe' => $downcoe,
            'expire_time' => $duration,
        ]);
        $record->insert();
        $coef = $seed->getCoefArray();
        $coef_item = $coef[0]; //复制栈顶
        $old_duration = $coef[0][2] - time();
        if ($duration == 0) {
            $replace = true;
        } else {
            if ($old_duration < $duration) {
                $replace = true;
            }
        }

        if ($upcoe >= 0) {
            $coef_item[0] = $upcoe;
        }
        if ($downcoe >= 0) {
            $coef_item[1] = $downcoe;
        }
        $coef_item[2] = $duration + time();

        //如果是永久有效，就直接替换栈顶的条目
        if ($replace) {
            $coef[0] = $coef_item;
        } else {
            array_unshift($coef, $coef_item);
        }

        $seed->setCoefArray($coef);
        $seed->save();
        $tmp = $seed->attributes;
        $tmp['discuz_pub_uid'] = $publisher->discuz_user_id;
        $ret['extra'] = $tmp;
        Yii::info($ret);
        return $ret;
    }

    public function actionSearch()
    {
        $form = new SeedSearchForm();
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            $res =  [
                'result' => 'failed',
                'extra' => $form->errors
            ];
        } else {
            $seeds = $form->searchSeeds();
            $res =  [
                'result' => 'succeed',
            ];
            $res['extra'] = [];
            /** @var Seed $seed */
            foreach ($seeds as $seed) {
                $res['extra'][] = $seed->seed_id;
            }
        }
        Yii::info($res);
        return $res;
    }

    public function actionFileList($seed_id)
    {
        /** @var Seed $seed */
        $seed = Seed::findOne($seed_id);
        if (empty($seed)) {
            return [
                'result' => 'failed',
                'extra' => 'no such seed',
            ];
        }
        $json = TorrentFileList::getFileListJson(
            UploadedSeedFile::getTorrentFilePath($seed->info_hash)
        );

        if (empty($json)) {
            return [
                'result' => 'failed',
                'extra' => 'decode error',
            ];
        } else {
            return [
                'result' => 'succeed',
                'extra' => $json,
            ];
        }
    }

    public function actionPeerInfo($seed_id)
    {
        /** @var Seed $seed */
        $seed = Seed::findOne($seed_id);
        if (empty($seed)) {
            return [
                'result' => 'failed',
                'extra' => 'no such seed',
            ];
        }

        $peers = $seed->peers;
        $leechers = [];
        $seeders = [];
        $all = [];
        foreach ($peers as $peer) {
            $peer->update_time =  strtotime($peer->update_time);
            $peer->create_time =  strtotime($peer->create_time);
            $peer->client_tag = substr(base64_decode($peer->client_tag), 0, 3);

            if ($peer->status == 'Seeder') {
                $seeders[] = $peer->attributes;
            } else {
                $leechers[] = $peer->attributes;
            }
            $all[] = $peer->attributes;
        }
        return [
            'result' => 'succeed',
            'extra' => [
                'all' => $all,
                'leechers' => $leechers,
                'seeders' => $seeders,
            ],
        ];
    }
}
