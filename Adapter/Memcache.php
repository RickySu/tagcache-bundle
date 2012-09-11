<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;

class Memcache extends TagCacheAdapter
{
    protected $Memcache = null;

    const MEMCACHE_OBJ_MAXSIZE = 1024000;

    public function __construct($NameSpace, $Options)
    {
        parent::__construct($NameSpace, $Options);
        $this->Memcache = new \Memcache();
        foreach ($Options['servers'] as $Server) {
            list($Host, $Port, $Weight) = explode(":", $Server);
            $this->Memcache->addServer($Host, $Port, true, $Weight);
        }
    }

    public function getLock($Key, $LockExpire = 3)
    {
        while (!$this->Memcache->add($this->buildKey(self::LOCK_PREFIX . $Key), 'LCK', false, $LockExpire)) {
            usleep(rand(1, 500));
        }

        return true;
    }

    public function releaseLock($Key)
    {
        return $this->Memcache->delete($this->buildKey(self::LOCK_PREFIX . $Key), 0);
    }

    protected function setRaw($key, $Obj, $expire = 0)
    {
        if (!$this->Options['enable_largeobject']) {
            return $this->Memcache->set($key, $Obj, 0, $expire);
        }
        if (@$this->Memcache->set($key, $Obj, 0, $expire)) {
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
                $this->Memcache->set($key, $KeyHash . $Chunkdata, 0, $expire);
            } else {
                $this->Memcache->set("$key:Chunk:$Start", $KeyHash . $Chunkdata, 0, $expire);
            }
            $Start+=self::MEMCACHE_OBJ_MAXSIZE;
        }

        return true;
    }

    protected function getRaw($key)
    {
        $Obj = $this->Memcache->get($key);
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
                    $Obj = $this->Memcache->get("$key:Chunk:$Start");
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

    protected function deleteRaw($key)
    {
        return $this->Memcache->delete($key, 0);
    }

    public function inc($key, $expire = 0)
    {
        $key = $this->buildKey($key);
        if ($this->Memcache->increment($key) === false) {
            return $this->Memcache->set($key, 1, 0, $expire);
        }

        return true;
    }

    public function dec($key, $expire = 0)
    {
        $key = $this->buildKey($key);
        if ($this->Memcache->decrement($key) === false) {
            return $this->Memcache->set($key, 0, 0, $expire);
        }

        return true;
    }

}
