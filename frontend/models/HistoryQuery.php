<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[History]].
 *
 * @see History
 */
class HistoryQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return History[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return History|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}