<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-1
 * Time: 上午4:22
 */

namespace common\library;

use common\library\BEncoder;
use frontend\models\User;
use Yii;

class TorrentFileTool
{
    public $info_array;
    public $torrent_array;
    public $file_count;
    public $total_size;
    /**
     * @param string $fileStr torrent文件的字符串
     * @return array|float|mixed|string 转换为UTF-8后的数组
     */
    public static function getProcessedObj($fileStr)
    {
        $be = new BEncoder();
        $torrent_array = $be->decode($fileStr);
        $info_array_orig = $torrent_array['info'];
        // 只保留我们感兴趣的信息
        $info_array = array(
            'name' => $info_array_orig['name'],
            'piece length' => $info_array_orig['piece length'],
            'pieces' => $info_array_orig['pieces'],
        );
        // 单文件的 torrent 不会有这个键
        if (isset($info_array_orig['files'])) {
            $info_array['files'] = $info_array_orig['files'];
        } else {
            // Fixed 2015-04-10 解决单文件种子大小统计为零错误
            $info_array['length'] = $info_array_orig['length'];
        }
        $info_array = static::convertTorrentEncoding($torrent, $info_array);
        $source_string = 'NGPT-' . strtoupper(sha1($be->encode($info_array)));
        $info_array['private'] = 1;
        $info_array['source'] = $source_string;

        $obj = new TorrentFileTool();

        $fstat = static::getStatisticInfo($info_array);

        $obj->file_count = $fstat['count'];
        $obj->total_size = $fstat['total_size'];
        $obj->info_array = $info_array;
        $obj->torrent_array = $torrent_array;
        return $obj;
    }

    private static function getStatisticInfo($info)
    {
        //考虑用gmp解决极大文件的问题
        if (isset($info['files'])) {
            $fi = $info['files'];
            $len = count($fi);
            $total = 0;
            /**
             * @var int $i
             */
            for ($i = 0; $i < $len; $i++) {
//                $total = gmp_add($total, $fi[$i]['length']);
                $total += $fi[$i]['length'];
            }
        } else {
            $len = 1;
            // Fixed 2015-04-10 解决单文件种子的大小和数量问题；当然，看 Decoder 的代码这里是可能为 int 可能为 float 的，为 float 应该不会超出最大允许大小了，不过精度降低
            $total = $info['length'];
        }

//        return array('count' => $len, 'total_size' => gmp_strval($total));
        return array('count' => $len, 'total_size' => strval($total));
    }

    public function getTorrentName()
    {
        return $this->info_array['name'];
    }

    public function getInfoEncoded()
    {
        $be = new BEncoder();
        return $be->encode($this->info_array);
    }

    private $info_hash;
    public function getInfoHash($recalc = false)
    {
        if (empty($this->info_hash) || $recalc) {
            $be = new BEncoder();
            $this->info_hash = strtoupper(sha1($be->encode($this->info_array)));
        }
        return $this->info_hash;
    }
    /**
     * @author Wu Tong
     * @param array $torrent_array
     * @param array $info_array
     * @return array
     */
    private static function convertTorrentEncoding(&$torrent_array, &$info_array)
    {
        // $torrent_array 为种子的根键值对
        // $info_array 为 info 节的实际内容键值对
        $need_conversion = false;
        // 两种情况才需要转换
        // 1: 存在强制的 encoding 设置，且不是 UTF-8
        // 2: 存在 codepage 设置 (Windows)
        // 否则默认为 UTF-8
        if ((!empty($torrent_array['encoding']) and strtolower($torrent_array['encoding']) != 'utf-8') or !empty($torrent_array['codepage'])) {
            $need_conversion = true;
        }
        /**
         * @var string $from_encoding
         */
        if ($need_conversion) {
            // 优先使用 encoding 设置
            if (!empty($torrent_array['encoding'])) {
                $from_encoding = strval($torrent_array['encoding']);
            } elseif (!empty($torrent_array['codepage'])) {
                $from_encoding = 'CP' . strval($torrent_array['codepage']);
            } else {
                return $info_array;
            }
        } else {
            return $info_array;
        }
        // 遇到不可转换的字符则丢弃（见 PHP 手册）
        $to_encoding = 'UTF-8//IGNORE';

        // 转换名称（包括单文件时的文件）与文件列表
        $info_array['name'] = iconv($from_encoding, $to_encoding, $info_array['name']);
        if (isset($info_array['files']) and is_array($info_array['files'])) {
            // 要使用完整的引用，看起来 PHP 对元素的使用是 copy on write 的
            // 使用简写例如 $v['path']，则不会影响原来的元素
            foreach ($info_array['files'] as $k => $v) {
                foreach ($info_array['files'][$k]['path'] as $index => $item) {
                    $info_array['files'][$k]['path'][$index] = iconv($from_encoding, $to_encoding, $item);
                }
            }
        }

        return $info_array;
    }

    /**
     * @param string $infoPath
     * @param string $mainTracker
     * @param array $backupTracker
     */
    public static function buildTorrentFile($infoPath, $mainTracker, $backupTracker)
    {
        $be = new BEncoder();
        $info = file_get_contents($infoPath);
        $info = $be->decode($info);
        if (empty($info)) {
            return null;
        }

        /** @var User $user */
        $user = User::findOne(Yii::$app->user->getId());
        $passkey = $user->passkey;
        $seed = [];
        $seed['announce'] = $mainTracker  . "passkey={$passkey}";
        foreach ($backupTracker as $tracker) {
            $seed['announce-list'] =
                $tracker . "passkey={$passkey}";
        }
        $seed['created date'] =
        $seed['created by'] = time();
        $seed['comment'] = 'Welcome To NGPT';
        $seed['encoding'] = 'UTF-8';
        $seed['info'] = $info;

        return $be->encode($seed);
    }
}
