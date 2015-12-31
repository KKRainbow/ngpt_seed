<?php

namespace frontend\models;

/**
 * This is the ActiveQuery class for [[Peer]].
 *
 * @see Peer
 */
class PeerQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Peer[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Peer|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}