<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class Apc extends TagCacheAdapter {

    const APC_OBJ_MAXSIZE = 1024000;

    public function __construct($NameSpace, $Options) {
        parent::__construct($NameSpace, $Options);
    }
    /**
     * Marge Split TagObject
     * @param string $Key
     * @return boolean
     */
    protected function MergeObj($Key) {
        $Obj = apc_fetch($this->buildKey("Value:$Key"));
        if (is_string($Obj)) {
            $KeyHash = md5($Key);
            if (substr($Obj, 0, 32) == $KeyHash) {
                //ChunkData
                $ChunkData = substr($Obj, 32);
                $Start = 0;
                while (true) {
                    $Start+=self::APC_OBJ_MAXSIZE;
                    $Obj = apc_fetch($this->buildKey("Value:$Key:Chunk:$Start"));
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
        return apc_fetch($this->buildKey("TagCache:$Tag"));
    }

    public function getTags($Key) {
        $Obj = apc_fetch($this->buildKey("Value:$Key"));
        if ($Obj instanceof TagCacheObj) {
            return $Obj->Tags;
        }
        return false;
    }

/*
    public function getLock($Key, $LockExpire = 5) {
        while (!apc_add($this->buildKey("TagCache:Lock:$Key"), 'LCK', $LockExpire)) {
            usleep(rand(1, 500));
        }
    }

    public function releaseLock($Key) {
        return apc_delete($this->buildKey("TagCache:Lock:$Key"));
    }
*/

    public function set($Key, $var, $Tags = array(), $expire = null) {
        if ($expire) {
            $expire+=time();
        }
        if (is_array($Tags)) {
            foreach ($Tags as $Tag) {
                if ($this->getTagUpdateTimestamp($Tag) === false) {
                    apc_store($this->buildKey("TagCache:$Tag"), time(), $expire);
                }
            }
        }
        $Obj = new TagCacheObj($var, $Tags, $expire);

        if (!$this->Options['enable_largeobject'] || strlen(serialize($Obj->Var)) < self::APC_OBJ_MAXSIZE) {
            return apc_store($this->buildKey("Value:$Key"), $Obj, $expire);
        }

        //Need to Split Data
        $Obj = serialize($Obj);
        $Start = 0;
        $KeyHash = md5($Key);
        while (true) {
            $Chunkdata = substr($Obj, $Start, self::APC_OBJ_MAXSIZE);
            if ($Chunkdata === false) {
                break;
            }
            if ($Start == 0) {
                apc_store($this->buildKey("Value:$Key"), $KeyHash . $Chunkdata, $expire);
            }
            else {
                apc_store($this->buildKey("Value:$Key:Chunk:$Start"), $KeyHash . $Chunkdata, $expire);
            }
            $Start+=self::APC_OBJ_MAXSIZE;
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
        return apc_delete($this->buildKey("Value:$Key"));
    }

    public function TagDelete($Tag) {
        return apc_delete($this->buildKey("TagCache:$Tag"));
    }

    public function clear() {
        return apc_clear_cache('user');
    }

}