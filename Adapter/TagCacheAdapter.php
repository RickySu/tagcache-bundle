<?php

namespace Ricky\TagCacheBundle\Adapter;

use Ricky\TagCacheBundle\TagCacheObj;

abstract class TagCacheAdapter {

    protected $Namespace = null;
    protected $Options = null;
    protected $hLock = array();

    public function __construct($NameSpace, $Options) {
        $this->Namespace = $NameSpace;
        $this->Options = $Options;
    }

    protected function buildKey($Key) {
        if ($this->Options['hashkey']) {
            return md5($this->Namespace . ':' . $Key);
        }
        return $this->Namespace . ':' . $Key;
    }

    /**
     * get Current Timestamp this is for debug use.
     * @return integer
     */
    protected function getCurrentTimestamp() {
        return time();
    }

    /**
     * get CacheObjectContent
     * @param mixed $TagCacheObj
     * @return mixed
     */
    protected function getTagCacheObjContent($TagCacheObj) {
        if (!($TagCacheObj instanceof TagCacheObj)) {
            return $TagCacheObj;
        }
        if ($TagCacheObj->expire && ($TagCacheObj->expire < $this->getCurrentTimestamp())) {
            return false;
        }
        if (is_array($TagCacheObj->Tags))
            foreach ($TagCacheObj->Tags as $Tag) {
                $TagTimestamp = $this->getTagUpdateTimestamp($Tag);
                if ($TagTimestamp === false) {
                    return false;
                }
                if ($TagTimestamp > $TagCacheObj->Timestamp) {
                    return false;
                }
            }
        return $TagCacheObj->Var;
    }

    abstract public function getTagUpdateTimestamp($Tag);

    abstract public function getTags($Key);

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
        }
        else {
            $hLock = $this->hLock[$LockFile];
            unset($this->hLock[$LockFile]);
        }
        flock($hLock, LOCK_UN);
        fclose($hLock);
        unlink($LockFile);
    }

    abstract public function set($key, $var, $Tags = array(), $expire = null);

    abstract public function get($key);

    abstract public function delete($key);

    abstract public function TagDelete($Tag);

    abstract public function clear();
}
