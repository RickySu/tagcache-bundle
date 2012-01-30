<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class Nullcache extends TagCacheAdapter {

    public function __construct($NameSpace, $Options) {
        parent::__construct($NameSpace, $Options);
    }

    public function getTagUpdateTimestamp($Tag) {
        return false;
    }

    public function getTags($Key) {
        return false;
    }

    public function getLock($Key, $LockExpire = 5) {

    }

    public function releaseLock($Key) {
        return false;
    }

    public function set($Key, $var, $Tags = array(), $expire = null) {
        return true;
    }

    public function get($Key) {
        return false;
    }

    public function delete($Key) {
        return true;
    }

    public function TagDelete($Tag) {
        return true;
    }

    public function clear() {
        return true;
    }

}