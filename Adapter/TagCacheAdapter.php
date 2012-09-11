<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\TagCacheObj;
use RickySu\TagCacheBundle\TagCacheTime;

abstract class TagCacheAdapter {

    const TAG_PREFIX = 'TagCacheBundle#Tag';
    const LOCK_PREFIX = 'TagCacheBundle#Lock';
    const TAG_CLEAR_ALL = '__TagCacheBundle__All';

    protected $Namespace = null;
    protected $Options = null;
    protected $hLock = array();

    public function __construct($NameSpace, $Options) {
        $this->Namespace = $NameSpace;
        $this->Options = $Options;
    }

    protected function buildKey($Key) {
        if ($this->Options['hashkey']) {
            return md5("{$this->Namespace}:$Key");
        }
        return "{$this->Namespace}:$Key";
    }

    protected function getLockFile($Key) {
        $LockFileDir = $this->Options['cache_dir'] . DIRECTORY_SEPARATOR . 'LCK';
        if (!file_exists($LockFileDir)) {
            mkdir($LockFileDir, 0777, true);
        }
        $KeyHash = md5($this->buildKey($Key));
        return $LockFileDir . DIRECTORY_SEPARATOR . "$KeyHash.lck";
        ;
    }

    public function getLock($Key, $LockExpire = 5) {
        $LockFile = $this->getLockFile($Key);
        @$Timestamp = (int) file_get_contents($LockFile);
        $hLock = fopen($LockFile, "w");
        if ($Timestamp + $LockExpire < time()) { //Old Lock File
            flock($hLock, LOCK_UN);
        }
        $StartTime = time();
        fwrite($hLock, $StartTime);
        while (!flock($hLock, LOCK_EX | LOCK_NB)) {
            usleep(rand(1, 500));
            if (time() - $StartTime > $LockExpire) { //Wait Lock Timeout
                flock($hLock, LOCK_UN);     //Steal Lock
                flock($hLock, LOCK_EX | LOCK_NB);
                break;
            }
        }
        $this->hLock[$LockFile] = $hLock;
    }

    public function releaseLock($Key) {
        $LockFile = $this->getLockFile($Key);
        if (!isset($this->hLock[$LockFile])) {
            $hLock = fopen($LockFile, "w");
        } else {
            $hLock = $this->hLock[$LockFile];
            unset($this->hLock[$LockFile]);
        }
        flock($hLock, LOCK_UN);
        fclose($hLock);
        unlink($LockFile);
    }

    /**
     * Store Cache
     * @param string $key
     * @param mixed $var
     * @param array $Tags
     * @param float $expire
     */
    public function set($key, $var, $Tags = array(), $expire = null) {
        if ($expire) {
            $expire+=TagCacheTime::time();
        }
        if (is_array($Tags)) {
            array_push($Tags, self::TAG_CLEAR_ALL);
            foreach ($Tags as $Tag) {
                if ($this->getTagUpdateTimestamp($Tag) === false) {
                    $this->setRaw($this->buildKey(self::TAG_PREFIX . $Tag), TagCacheTime::time());
                }
            }
        }
        $Obj = new TagCacheObj($var, $Tags, $expire);
        return $this->setRaw($this->buildKey($key), $Obj, $expire);
    }

    /**
     * get cache
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        $Obj = $this->getRaw($this->buildKey($key));
        if ($Obj instanceof TagCacheObj) {
            $Data = $Obj->getVar($this);
            if ($Data === false) {
                $this->delete($key);
            }
            return $Data;
        }
        return $Obj;
    }

    /**
     * delete cache
     * @param string $key
     * @return bool
     */
    public function delete($key) {
        return $this->deleteRaw($this->buildKey($key));
    }

    /**
     * tag delete
     * @param string $Tag
     * @return bool
     */
    public function deleteTag($Tag) {
        return $this->deleteRaw($this->buildKey(self::TAG_PREFIX . $Tag));
    }

    /**
     * clear all cache
     * @return bool
     */
    public function clear() {
        return $this->deleteTag(self::TAG_CLEAR_ALL);
    }

    /**
     * get tag updated timestamp
     * @param string $key
     * @return float
     */
    public function getTagUpdateTimestamp($tag) {
        return $this->getRaw($this->buildKey(self::TAG_PREFIX . $tag));
    }

    /**
     * get tags
     * @param type $key
     * @return boolean
     */
    public function getTags($key) {
        $Obj = $this->getRaw($this->buildKey($key));
        if ($Obj instanceof TagCacheObj) {
            return $Obj->getTags();
        }
        return array();
    }

    /**
     * increment a key
     * @param type $key
     * @param type $expire
     * @return boolean
     */
    public function inc($key, $expire = 0) {
        $this->getLock($key);
        $this->set($key,(int)$this->get($key)+1);
        $this->releaseLock($key);
        return true;
    }

    /**
     * decrement a key
     * @param type $key
     * @param type $expire
     * @return boolean
     */
    public function dec($key, $expire = 0) {
        $this->getLock($key);
        $this->set($key,(int)$this->get($key)-1);
        $this->releaseLock($key);
        return true;
    }

    abstract protected function getRaw($key);

    abstract protected function setRaw($key, $Obj, $expire);

    abstract protected function deleteRaw($key);
}
