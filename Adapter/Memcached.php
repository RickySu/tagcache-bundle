<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class Memcached extends TagCacheAdapter {

    protected $Memcached = null;

    const MEMCACHE_OBJ_MAXSIZE = 1024000;

    public function __construct($NameSpace, $Options) {
        parent::__construct($NameSpace, $Options);
        $this->Memcached = new \Memcached();
        foreach ($Options['servers'] as $Server) {
            list($Host, $Port, $Weight) = explode(":", $Server);
            $this->Memcached->addServer($Host, $Port, $Weight);
        }
    }

    public function getLock($Key, $LockExpire = 3) {
        while (!$this->Memcached->add($this->buildKey(self::LOCK_PREFIX . $Key), 'LCK', $LockExpire)) {
            usleep(rand(1, 500));
        }
        return true;
    }

    public function releaseLock($Key) {
        return $this->Memcached->delete($this->buildKey(self::LOCK_PREFIX . $Key));
    }

    protected function setRaw($key, $Obj, $expire = 0) {
        if (!$this->Options['enable_largeobject']) {
            return $this->Memcached->set($key, $Obj, $expire);
        }
        if (@$this->Memcached->set($key, $Obj, $expire)) {
            return true;
        }
        //Need to Split Data
        $Obj = serialize($Obj);
        $Start = 0;
        $KeyHash = md5($key);
        while (true) {
            $Chunkdata = substr($Obj, $Start, self::MEMCACHE_OBJ_MAXSIZE);
            if ($Chunkdata === false) {
                break;
            }
            if ($Start == 0) {
                $this->Memcached->set($key, $KeyHash . $Chunkdata, $expire);
            } else {
                $this->Memcached->set("$key:Chunk:$Start", $KeyHash . $Chunkdata, $expire);
            }
            $Start+=self::MEMCACHE_OBJ_MAXSIZE;
        }
        return true;
    }

    protected function getRaw($key) {
        $Obj = $this->Memcached->get($key);
        if (!$this->Options['enable_largeobject']) {
            return $Obj;
        }
        if (is_string($Obj)) {
            $KeyHash = md5($key);
            if (substr($Obj, 0, 32) == $KeyHash) {
                //ChunkData
                $ChunkData = substr($Obj, 32);
                $Start = 0;
                while (true) {
                    $Start+=self::MEMCACHE_OBJ_MAXSIZE;
                    $Obj = $this->Memcached->get("$key:Chunk:$Start");
                    if ($Obj === false) {
                        break;
                    }
                    if (substr($Obj, 0, 32) != $KeyHash) {
                        return false;
                    }
                    $ChunkData.=substr($Obj, 32);
                }
                $Obj = unserialize($ChunkData);
                unset($ChunkData);
            }
        }
        return $Obj;
    }

    protected function deleteRaw($key) {
        return $this->Memcached->delete($key);
    }
    public function inc($key, $expire = 0) {
        $key = $this->buildKey($key);
        if ($this->Memcached->increment($key) === false) {
            return $this->Memcached->set($key, 1, $expire);
        }
        return true;
    }

    public function dec($key, $expire = 0) {
        $key = $this->buildKey($key);
        if ($this->Memcached->decrement($key) === false) {
            return $this->Memcached->set($key, 0, $expire);
        }
        return true;
    }

}