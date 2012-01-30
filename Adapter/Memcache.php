<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class Memcache extends TagCacheAdapter {

    protected $MemcacheInstance = null;

    const MEMCACHE_OBJ_MAXSIZE = 1024000;

    public function __construct($NameSpace, $Options) {
        parent::__construct($NameSpace, $Options);
        $this->MemcacheInstance = new \Memcache();
        foreach ($Options['servers'] as $Server) {
            list($Host, $Port, $Weight) = explode(":", $Server);
            $this->MemcacheInstance->addServer($Host, $Port, true, $Weight);
        }
    }

    /**
     * Marge Split TagObject
     * @param string $Key
     * @return boolean
     */
    protected function MergeObj($Key) {
        $Obj = $this->MemcacheInstance->get($this->buildKey("Value:$Key"));
        if (is_string($Obj)) {
            $KeyHash = md5($Key);
            if (substr($Obj, 0, 32) == $KeyHash) {
                //ChunkData
                $ChunkData = substr($Obj, 32);
                $Start = 0;
                while (true) {
                    $Start+=self::MEMCACHE_OBJ_MAXSIZE;
                    $Obj = $this->MemcacheInstance->get($this->buildKey("Value:$Key:Chunk:$Start"));
                    if ($Obj === false)
                        break;
                    if (substr($Obj, 0, 32) != $KeyHash)
                        return false;
                    $ChunkData.=substr($Obj, 32);
                }
                $Obj = unserialize($ChunkData);
            }
        }
        return $Obj;
    }

    public function getTagUpdateTimestamp($Tag) {
        return $this->MemcacheInstance->get($this->buildKey("TagCache:$Tag"));
    }

    public function getTags($Key) {
        $Obj = $this->MergeObj($Key);
        if ($Obj instanceof TagCacheObj) {
            return $Obj->Tags;
        }
        return false;
    }

    public function getLock($Key, $LockExpire = 5) {
        while (!$this->MemcacheInstance->add($this->buildKey("TagCache:Lock:$Key"), 'LCK', false, $LockExpire)) {
            usleep(rand(1, 500));
        }
    }

    public function releaseLock($Key) {
        return $this->MemcacheInstance->delete($this->buildKey("TagCache:Lock:$Key"), 0);
    }

    public function set($Key, $var, $Tags = array(), $expire = null) {
        if ($expire) {
            $expire+=time();
        }
        if (is_array($Tags)) {
            array_push($Tags, '__TagCache__All');
            foreach ($Tags as $Tag) {
                if ($this->getTagUpdateTimestamp($Tag) === false) {
                    $this->MemcacheInstance->set($this->buildKey("TagCache:$Tag"), time(), null, $expire);
                }
            }
        }
        $Obj = new TagCacheObj($var, $Tags, $expire);
        if (!$this->Options['enable_largeobject'] || strlen(serialize($Obj->Var)) < self::MEMCACHE_OBJ_MAXSIZE) {
            return $this->MemcacheInstance->set($this->buildKey("Value:$Key"), $Obj, null, $expire);
        }

        //Need to Split Data
        $Obj = serialize($Obj);
        $Start = 0;
        $KeyHash = md5($Key);
        while (true) {
            $Chunkdata = substr($Obj, $Start, self::MEMCACHE_OBJ_MAXSIZE);
            if ($Chunkdata === false) {
                break;
            }
            if ($Start == 0) {
                $this->MemcacheInstance->set($this->buildKey("Value:$Key"), $KeyHash . $Chunkdata, null, $expire);
            }
            else {
                $this->MemcacheInstance->set($this->buildKey("Value:$Key:Chunk:$Start"), $KeyHash . $Chunkdata, null, $expire);
            }
            $Start+=self::MEMCACHE_OBJ_MAXSIZE;
        }
        return true;
    }

    public function get($Key) {
        $Obj = $this->MergeObj($Key);
        $Data = $this->getTagCacheObjContent($Obj);
        if ($Data === false) {
            $this->delete($Key);
        }
        return $Data;
    }

    public function delete($Key) {
        return $this->MemcacheInstance->delete($this->buildKey("Value:$Key"), 0);
    }

    public function TagDelete($Tag) {
        return $this->MemcacheInstance->delete($this->buildKey("TagCache:$Tag"), 0);
    }

    public function clear() {
        return $this->TagDelete('__TagCache__All');
    }

}