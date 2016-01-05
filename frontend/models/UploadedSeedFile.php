<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-1
 * Time: 上午3:51
 */

namespace frontend\models;

use common\library\BEncoderException;
use common\library\MetaInfoProcessor;
use common\library\TorrentFileTool;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use common\library\BEncoder;

class UploadedSeedFile extends Model
{
    /** @var UploadedFile $torrentFile */
    public $torrentFile;

    public $pub_form;
    public $type_id;
    public $meta_info_id;

    private $hasValidated = false;
    /**
     * @var MetaInfoProcessor $proc_obj
     */
    private $proc_obj;

    public function getSubject()
    {
        return $this->proc_obj->generateSubject();
    }

    public static function getMetaInfo($fid)
    {
        return file_get_contents('meta/' . $fid . '.json');
    }

    public function rules()
    {
        return [
            [['torrentFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'torrent'],
            [['pub_form', 'meta_info_id'], 'required'],
            [['pub_form', 'type_id', 'meta_info_id'], function ($attr, $params) {
                if ($this->hasValidated) {
                    return;
                }
                $this->hasValidated = true;
                Yii::trace("called");
                $json = $this->getMetaInfo($this->meta_info_id);
                $this->pub_form = json_decode($this->pub_form, true);
                Yii::info($json);
                Yii::info($this->pub_form);
                $proc = new MetaInfoProcessor($json, $this->pub_form, $this->type_id);
                $res = $proc->checkFields();
                Yii::info($res, static::classname());
                if (!empty($res)) {
                    if (!empty($res['forgotten'])) {
                        $this->addError(
                            "pub_form",
                            "未填写：" . implode(',', $res['forgotten'])
                        );
                    }
                    if (!empty($res['unmatched'])) {
                        $this->addError(
                            "pub_form",
                            "格式错误：" . implode(',', $res['unmatched'])
                        );
                    }
                }
                $this->proc_obj = $proc;
            }],
        ];
    }

    /**
     * @param string $retval exists|invalid|succeed|failed
     * @param bool $replace
     * @return false|Seed
     * @throws \Exception
     */
    public function upload(&$retval, $replace = false)
    {
        if ($this->validate()) {
            //解析该种子
            $torrent = file_get_contents($this->torrentFile->tempName);
            /** @var TorrentFileTool $obj */
            $obj = null;
            try {
                $obj = TorrentFileTool::getProcessedObj($torrent);
            } catch (BEncoderException $ex) {
                $obj = null;
                return false;
            }

            /** @var Seed|null $seedExists */
            $seedExists = Seed::findOne(['info_hash' => $obj->getInfoHash()]);
            $seed = null;
            if ($seedExists) {
                if (!$seedExists->is_valid) {
                    //TODO 参数化配置是否允许删除后重新上传
                    if (true) {
                        $seed = $seedExists;
                    } else {
                        $seedExists->addError('info_hash', 'seed invalid');
                        $retval = 'invalid';
                        return $seedExists;
                    }
                } elseif (!$replace) {
                    $seedExists->addError('info_hash', 'seed exists');
                    $retval = 'exists';
                    return $seedExists;
                } else {
                    $seed = $seedExists;
                }
            } else {
                $seed = new Seed();
            }

            file_put_contents(
                "torrents/" . $obj->getInfoHash() . ".info",
                $obj->getInfoEncoded()
            );
            $seed->file_count = $obj->file_count;
            $seed->file_size = $obj->total_size;
            $seed->info_hash = $obj->getInfoHash();
            $seed->torrent_name = $obj->getTorrentName();
            $seed->publisher_user_id = Yii::$app->user->identity->getId();
            $seed->detail_info = $this->proc_obj->getDetail();
            $seed->full_name = $this->proc_obj->generateSubject();
            $seed->is_valid = true;
            $seed->save();
            $retval = 'succeed';
            return $seed;
        } else {
            $retval = 'failed';
            return false;
        }
    }
}
