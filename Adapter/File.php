<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class File extends TagCacheAdapter {

    protected $CacheBaseDir;

    public function __construct($NameSpace, $Options) {
        parent::__construct($NameSpace, $Options);
        $this->CacheBaseDir = $Options['cache_dir'];
    }

    protected function getCacheFile($Key) {
        $KeyHash = md5($this->buildKey($Key));
        $CachePath = $this->CacheBaseDir . DIRECTORY_SEPARATOR . 'FileCache' . DIRECTORY_SEPARATOR . $KeyHash{0} . DIRECTORY_SEPARATOR . $KeyHash{1} . DIRECTORY_SEPARATOR . $KeyHash{2};
        if (!file_exists($CachePath)) {
            mkdir($CachePath, 0777, true);
        }
        return "$CachePath/$KeyHash";
    }

    /**
     * Store Value to File.
     * @param string $Key
     */
    protected function setRaw($Key, $Val, $expire = 0) {
        $CacheFile = $this->getCacheFile($Key);
        return file_put_contents($CacheFile, serialize($Val)) > 0;
    }

    /**
     * Delete Value From File
     * @param string $Key
     * @return boolean
     */
    protected function deleteRaw($Key) {
        $CacheFile = $this->getCacheFile($Key);
        if (file_exists($CacheFile)) {
            return unlink($CacheFile);
        }
        return true;
    }

    /**
     * Read Value From File
     * @param string $Key
     */
    protected function getRaw($Key) {
        $CacheFile = $this->getCacheFile($Key);
        if (!file_exists($CacheFile)) {
            return false;
        }
        return unserialize(file_get_contents($CacheFile));
    }

}