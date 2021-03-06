<?php
/**
 * Created by PhpStorm.
 * User: sunsijie
 * Date: 4/17/15
 * Time: 1:18 PM
 */

namespace common\library;

use Yii;

class TorrentFileList
{
    /**
     * @param int $size size in bytes
     * @return string
     */
    private static function fileSizeFormat($size)
    {
        $unit = ['Byte', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $size = (double)$size;
        $i = 0;
        while ($size >= 1023.9) {
            $i++;
            $size /= 1024;
        }
        $size = number_format($size, 2);
        return $size . $unit[$i];
    }

//函数用于根据infohash生成目录树结构
    /**
     * @param string $filename
     * @return array($treestruct,$maxdeep)
     */
    public static function getFileListJson($filename)
    {
        $content = file_get_contents($filename);
        $decoder = new BEncoder();
        $id = function () {
            static $id = 0;
            return $id++;
        };
        try {
            $decoder->decode($content);
        } catch (BEncoderException $e) {
            return null;
        }

        $res = $decoder->source;
        //Single file
        if (isset($res['length'])) {
            $json['text'] = $res['name'];
            $json['id'] = $id();
            $json['size'] = static::fileSizeFormat($res['length']);
            $json['type'] = 'f'; //单一文件
            return array(json_encode($json), 0);
        } elseif (isset($res['files'])) {
            $genEntry = function ($dirarr, $len, &$root) {
                $currEntry = &$root;
                for ($i = 0; $i < count($dirarr); $i++) {
                    //file item
                    if ($i == count($dirarr) - 1) {
                        array_push($currEntry, array(
                            'text' => array_pop($dirarr), //Append len info
                            'type' => 'f',
                            'size' => static::fileSizeFormat($len)
                        ));
                        return;
                    }
                    $dir = &$dirarr[$i];
                    //Entry exists?
                    $flag = false;
                    for ($j = 0; $j < count($currEntry); $j++) {
                        $entry = $currEntry[$j];
                        if (isset($entry['text']) && $entry['text'] == $dir) {
                            $flag = true;
                            break;
                        }
                    }
                    if ($flag == false) {
                        $tmp = [
                            'text' => $dir,
                            'type' => 'd',
                            'size' => -1,
                            'children' => array()
                        ];
                        $n = array_push($currEntry, $tmp);
                        $newentry = &$currEntry[$n - 1];
                    } else {
                        $newentry = &$currEntry[$j];
                    }
                    //Is children exists?
                    if (!isset($newentry['children'])) {
                        $newentry['children'] = array();
                    }
                    unset($currEntry);
                    $currEntry = &$newentry['children'];
                } //Structure is built already
            };
            //Set root
            $json = [
                'text' => $res['name'],
                'type' => 't', //title
                'size' => '-1',
                'children' => array()
            ];
            $maxdepth = 1;
            foreach ($res['files'] as $file) {
                if (count($file['path']) > $maxdepth) {
                    $maxdepth = count($file['path']);
                }
                $genEntry($file['path'], $file['length'], $json['children']);
            }
            return array(json_encode($json), $maxdepth);
        } else {
            return null;
        }
    }
}
