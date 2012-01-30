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
    protected function setRaw($Key, $Val) {
        $CacheFile = $this->getCacheFile($Key);
        file_put_contents($CacheFile, serialize($Val));
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

    public function getTagUpdateTimestamp($Tag) {
        return $this->getRaw("TagCache:$Tag");
    }

    public function getTags($Key) {
        $Obj = $this->getRaw($Key);
        if ($Obj instanceof TagCacheObj) {
            return $Obj->Tags;
        }
        return false;
    }

    public function set($Key, $var, $Tags = array(), $expire = null) {
        if ($expire) {
            $expire+=time();
        }
        if (is_array($Tags)) {
            array_push($Tags, '__TagCache__All');
            foreach ($Tags as $Tag) {
                if ($this->getTagUpdateTimestamp($Tag) === false)
                    $this->setRaw("TagCache:$Tag", time());
            }
        }
        $Obj = new TagCacheObj($var, $Tags, $expire);
        $this->setRaw($Key, $Obj);
        return true;
    }

    public function get($Key) {
        $Obj = $this->getRaw($Key);
        if ($Obj instanceof TagCacheObj) {
            $Data = $this->getTagCacheObjContent($Obj);
            if ($Data === false){
                $this->delete($Key);
            }
            return $Data;
        }
        return $Obj;
    }

    public function delete($Key) {
        return $this->deleteRaw($Key);
    }

    public function TagDelete($Tag) {
        return $this->deleteRaw("TagCache:$Tag");
    }

    public function clear() {
        return $this->TagDelete('__TagCache__All');
    }

}