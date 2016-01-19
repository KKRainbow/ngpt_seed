<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-6
 * Time: 上午6:18
 */

namespace frontend\models;

use Yii;
use yii\base\Model;

class SeedSearchForm extends Model
{
    public $type_subtype_assoc; //[ [type => sub_type] ]
    public $search_text;
    public $upcoe_max;
    public $upcoe_min;
    public $downcoe_max;
    public $downcoe_min;
    public $date_after; //距离现在的天数
    public $order_by;
    public $order_type;
    public $limit;
    public $offset;
    public $nodead;

    public function rules()
    {
        return [
            ['type_subtype_assoc', function ($attr, $params) {
                $this->$attr = json_decode($this->$attr, true);
                Yii::info($this->$attr);
                if ($this->$attr === null) {
                    $this->addError($attr, 'type pair must be json');
                } elseif (!is_array($this->$attr)) {
                    $this->addError($attr, 'type pair must be array');
                }
            }
            ],
            ['search_text', 'trim'],
            ['search_text', 'string', 'length' => [1,300]],
            ['limit', 'default', 'value' => 40],
            ['offset', 'default', 'value' => 0],
            ['nodead', 'default', 'value' => false],
            ['order_by', 'default', 'value' => ['seeder_count']],
            ['order_type', 'default', 'value' => ['desc']],
            [['limit', 'offset'], 'integer'],
            [['upcoe_max', 'downcoe_max'], 'default', 'value' => 999999],
            ['date_after', 'default', 'value' => 1500],
            [['upcoe_min', 'downcoe_min'], 'default', 'value' => 0],
            [
                [
                    'upcoe_max', 'upcoe_min',
                    'downcoe_max', 'downcoe_min',
                    'date_after'
                ],
                'integer'
            ],
            ['order_by', 'in', 'range' =>
                ['seeder_count', 'leecher_count','completed_count','upcoe'],
                'allowArray' => true,
            ],
            ['order_type', 'in', 'range' => //与order_by一一对应
                ['asc', 'desc'],
                'allowArray' => true,
            ],
        ];
    }

    private function getFormHash()
    {
        return md5(json_encode($this->attributes));
    }

    public function searchSeeds()
    {
        $keywords = explode(' ', $this->search_text);
        array_walk($keywords, function (&$value) {
            $value = strtolower($value);
        });
        $qr = Seed::find()->select('seed_id')->
        where(['like', 'lower(full_name)', $keywords])->
        andWhere("coefs_stack[1][1] BETWEEN $this->upcoe_min AND $this->upcoe_max")->
        andWhere("coefs_stack[1][2] BETWEEN $this->downcoe_min AND $this->downcoe_max")->
        andWhere("pub_time >= 'today'::date - '{$this->date_after} days'::interval");
        $cond = [];
        foreach ($this->type_subtype_assoc as $type => $sub_type) {
            $tmpc = " (\"type_id\"='$type' ";
            if (!empty($sub_type)) {
                $tmpc .= " AND \"sub_type_id\"='$sub_type') ";
            } else {
                $tmpc .= ")";
            }
            $cond[] = $tmpc;
        }
        if (!empty($cond)) {
            $qr->andWhere(implode('OR', $cond));
        }

        foreach ($this->order_by as $idx => $by) {
            $type = strtolower($this->order_type[$idx]) == 'asc' ? SORT_ASC : SORT_DESC;
            $qr->addOrderBy([$by => $type]);
        }

        if ($this->nodead) {
            $qr->andWhere(['between', 'seeder_count', 1, 100000]);
        }
        Yii::info($qr->where);
        return $qr->limit($this->limit)->offset($this->offset)->all();
    }
}
