<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[Seed]].
 *
 * @see Seed
 */
class SeedQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Seed[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Seed|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}