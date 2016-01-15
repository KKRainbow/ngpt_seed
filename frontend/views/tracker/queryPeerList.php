<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 15-12-31
 * Time: 下午11:16
 */
use common\library\BEncoder;
use frontend\models\Peer;

function respond($arr)
{
    Yii::info("Response send");
    Yii::info($arr);
    $be = new BEncoder();
    $be->encode($arr);
    echo $be->encoded;
}

function compactV4Peer(Peer $peer, &$res)
{
    $tmp = str_pad(pack('Nn', ip2long($peer->ipv4_addr), $peer->ipv4_port), 6);
    $res .= $tmp;
}

function compactV6Peer(Peer $peer, &$res)
{
    $tmp = inet_pton($peer->ipv6_addr) .
        str_pad(pack('n', $peer->ipv6_port), 2);
    $tmp = str_pad($tmp, 18);
    $res .= $tmp;
}

function incompactV4Peer(Peer $peer, &$res)
{
    global $form;
    $tmp = array();
    if ($form->no_peer_id != 1) {
        $tmp['peer id'] = strval($peer->client_tag);
    }
    $tmp['ip'] = strval($peer->ipv4_addr);
    $tmp['port'] = intval($peer->ipv4_port);
    array_push($res, $tmp);
}

function incompactV6Peer(Peer $peer, &$res)
{
    global $form;
    if ($form->no_peer_id != 1) {
        $tmp['peer id'] = strval($peer->client_tag);
        $tmp['ip'] = strval($peer->ipv6_addr);
        $tmp['port'] = intval($peer->ipv6_port);
        array_push($res, $tmp);
    }
}

$response_arr = [];

/** @var array $errors */
if (!empty($form->errors)) {
    $response_arr['failure reason'] = print_r($form->errors, true);
} else {
    $peers4 = null;
    $peers6 = null;
    if (!$form->compact) {
        $response_arr['peers'] = array();
        $peers4 = &$response_arr['peers'];
        $peers6 = &$response_arr['peers'];
    } else {
        $response_arr['peers'] = "";
        $response_arr['peers6'] = "";
        $peers4 = &$response_arr['peers'];
        $peers6 = &$response_arr['peers6'];
    }
    $complete = 0;
    $incomplete = 0;
    $hasv4 = false;
    $hasv6 = false;
    /** @var Peer $peer */
    foreach ($peer_list as $peer) {
        if ($peer->status == 'Seeder') {
            $complete++;
        } else {
            $incomplete++;
        }
        if (!$form->compact) {
            if (empty($peer->ipv6_addr)) {
                incompactV4Peer($peer, $peers4);
                $hasv4 = true;
            } else {
                incompactV6Peer($peer, $peers6);
                $hasv6 = true;
            }
        } else {
            if (empty($peer->ipv6_addr)) {
                compactV4Peer($peer, $peers4);
                $hasv4 = true;
            } else {
                compactV6Peer($peer, $peers6);
                $hasv6 = true;
            }
        }
    }
    if (!$hasv4) {
        unset($response_arr['peers']);
    }
    if (!$hasv6) {
        unset($response_arr['peers6']);
    }

    $response_arr['complete'] = $complete;
    $response_arr['incomplete'] = $incomplete;
}
$response_arr['tracker id'] = 'ngpt';
$response_arr['interval'] = 600;
$response_arr['min interval'] = 120;
respond($response_arr);
