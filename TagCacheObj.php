<?php

namespace RickySu\TagCacheBundle;

class TagCacheObj {

    public $Var;
    public $Tags;
    public $Timestamp;
    public $expire;

    /**
     * @param mixed $Var   Content to Store in Cache
     * @param array $Tags  Tags
     * @param integer $expire  Object Expire time.
     */
    public function __construct($Var, $Tags, $expire = 0) {
        $this->Var = $Var;
        $this->Tags = $Tags;
        $this->Timestamp = time();
        $this->expire = $expire;
    }

}