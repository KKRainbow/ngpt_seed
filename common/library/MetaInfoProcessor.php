<?php
/**
 * Created by PhpStorm.
 * User: sunsijie
 * Date: 4/30/15
 * Time: 1:25 PM
 */

namespace common\library;

use Yii;

/**
 * Class MetaInfoProcessor
 * @property string $detail
 * @package common\library
 */
class MetaInfoProcessor
{
    public $json;
    public $post;
    public $pre;
    private $detail = [];

    /**
     * 去除所有的违规强调字符。
     * @param string $string
     * @param bool $incbracket 是否包含普通的中括号。false 适用于对整个标题的处理，true 适用于对其中一项的处理。
     * @return string
     */
    public function filteremphases(&$string, $incbracket = true)
    {
        if (empty($string)) {
            return '';
        }
        $empharr = array(
            '[【]', '[】]', '[〖]', '[〗]', '[『]', '[』]', '[「]', '[」]',
        );
        $replacearr = array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        );
        if ($incbracket) {
            $empharr[] = '[\[]';
            $empharr[] = '[\]]';
            $replacearr[] = ' ';
            $replacearr[] = ' ';
        }
        $string = preg_replace($empharr, $replacearr, $string);
        $string = trim($string);
        return $string;
    }

    /**
     * @param array $array
     * @return array
     */
    public function xfilteremphases(&$array)
    {
        if (is_array($array) && !empty($array)) {
            foreach ($array as $k => $v) {
                $array[$k] = $this->filteremphases($v);
            }
            return $array;
        } else {
            return array();
        }
    }

    public function __construct($meta, $post, $type_id = null)
    {
        $p = array();
        foreach ($post as $k => $v) {
            $p[$k] = $v;
        }
        $p = $this->xfilteremphases($p);
        // 解除原引用
        $post = $p;

        $json = json_decode($meta);

        if ($json == null) {
            throw new \Exception("服务器端格式错误");
        }
        //根据typeid判断采取哪一个
        if (isset($json->none)) {
            $this->json = $json->none;
            $this->pre = $json->none->name_prefix;
        } else {
            $this->json = $json->$type_id;
        }
        $this->post = $post;
    }

    public function n($name, $suffix = '')
    {
        return $this->pre . "_" . $name . "_" . $suffix;
    }

    //检查是否漏填以及是否违反regex
    public function checkFields()
    {
        $hasForgotten = false;
        $hasUnmatch = false;
        $form = $this->json->form;
        $forgotten_array = [];
        $unmatched_array = [];
        foreach ($form as $v) {
            if (!$v->optional && (
                    !isset($this->post[$v->name]) || strlen($this->post[$v->name]) == 0)
            ) {
                //这里这样写是没有问题的,因为表单里面有file类型那么$_FILES一定存在相应的key
                if ($v->type == 'file') {
                    //file是由UploadedSeedFile来检查的。
                    continue;
                }
                $forgotten_array[] = $v->title;
                $hasForgotten = true;
                continue;
            }
            //判断Select是否都在Options中,防止被篡改
            if (($v->type == 'select' || $v->type == 'radios' || $v->type == 'multiple')
                && $v->user_defined == false
            ) {
                $options = array_keys(get_object_vars($v->optiongroup[0]->options));
                if (!in_array($this->post[$v->name], $options)) {
                    $unmatched_array[] = $v->title;
                    $hasUnmatch = true;
                }
            }
            //根据regex,判断输入是否合法
            if (isset($v->regex) && strlen($v->regex) > 0) {
                Yii::info($v->regex);
                if (!preg_match('/' . $v->regex . '/', $this->post[$v->name])) {
                    $hasUnmatch = true;
                    $unmatched_array[] = $v->title;
                }
            }
        }
        $res = [];
        if ($hasForgotten) {
            $res['forgotten'] = $forgotten_array;
        }
        if ($hasUnmatch) {
            $res['unmatched'] = $unmatched_array;
        }
        return $res;
        //检验标题格式
        $subjectFormat = $this->generateSubject();
    }

    public function fillFields()
    {
        //按顺序放到f数组里就好,存不下的不存
        $index = 0;
        $c = count($this->json->form);
        while ($index < $c) {
            while (!isset($this->post[$this->json->form[$index]->name])) {
                $index++;
            }
            $name = $this->json->form[$index]->name;
            $this->detail[$this->json->form[$index]->title] = $this->post[$name];
            $index++;
        }
        return $this->detail;
    }

    public function getDetail()
    {
        if (empty($this->detail)) {
            $this->fillFields();
        }
        return json_encode($this->detail);
    }

    public function generateSubject()
    {
        $arr = $this->json->subject;
        $res = null;
        foreach ($arr as $tag) {
            $flag = false;
            //遍历form
            foreach ($this->json->form as $info_item) {
                if ($info_item->name == $tag) {
                    if (!$info_item->optional || !empty($this->post[$info_item->name])) {
                        if (strlen($this->post[$info_item->name]) != 0) {
                            $res .= '[' . $this->post[$info_item->name] . ']';
                        }
                    }
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                Yii::error("请检查meta info是否正确！！");
                throw new \Exception("请检查metainfo是否正确");
            }
        }
        return $res;
    }
}

