<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-13
 * Time: 上午7:43
 */

namespace console\controllers;

use frontend\models\Peer;
use yii\console\Controller;

class PeerController extends Controller
{
    public function actionDeleteStalePeer()
    {
        Peer::deleteAll("update_time <= now() - '1 hour'::interval");
    }
}