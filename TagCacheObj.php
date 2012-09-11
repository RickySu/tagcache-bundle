<?php

namespace RickySu\TagCacheBundle;

class TagCacheObj {

    public $Var;
    public $Tags;
    public $Timestamp;
    public $expire;
    public $Chunked = 0;

    /**
     * @param mixed $Var
     * @param array $Tags
     * @param TagMemcache $TagMemcache
     */
    public function __construct($Var, $Tags, $expire = 0) {
        $this->Var = $Var;
        $this->Tags = $Tags;
        $this->Timestamp = TagCacheTime::time();
        $this->expire = $expire;
    }

    public function getVar($CacheAdapter) {
        if ($this->expire && ($this->expire < TagCacheTime::time())) {
            return false;
        }
        if (is_array($this->Tags))
            foreach ($this->Tags as $Tag) {
                $TagTimestamp = $CacheAdapter->getTagUpdateTimestamp($Tag);
                if ($TagTimestamp === false) {
                    return false;
                }
                if ($TagTimestamp > $this->Timestamp) {
                    return false;
                }
            }
        return $this->Var;
    }

    public function getTags() {
        return $this->Tags;
    }

}