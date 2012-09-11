<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class Apc extends TagCacheAdapter {

    const APC_OBJ_MAXSIZE = 1024000;

    public function __construct($NameSpace, $Options) {
        parent::__construct($NameSpace, $Options);
    }

    protected function setRaw($key, $Obj, $expire = 0) {
        if (!$this->Options['enable_largeobject']) {
            return apc_store($key, $Obj, $expire);
        }
        if (@apc_store($key, $Obj, $expire)) {
            return true;
        }
        //Need to Split Data
        $Obj = serialize($Obj);
        $Start = 0;
        $KeyHash = md5($key);
        while (true) {
            $Chunkdata = substr($Obj, $Start, self::APC_OBJ_MAXSIZE);
            if ($Chunkdata === false) {
                break;
            }
            if ($Start == 0) {
                apc_store($key, $KeyHash . $Chunkdata, $expire);
            } else {
                apc_store("$key:Chunk:$Start", $KeyHash . $Chunkdata, $expire);
            }
            $Start+=self::APC_OBJ_MAXSIZE;
        }
        return true;
    }

    protected function getRaw($key) {
        $Obj = apc_fetch($key);
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
                    $Start+=self::APC_OBJ_MAXSIZE;
                    $Obj = apc_fetch("$key:Chunk:$Start");
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
        return apc_delete($key);
    }

    public function inc($key, $expire = 0) {
        $key = $this->buildKey($key);
        if (apc_inc($key) === false) {
            return apc_store($key, 1, $expire);
        }
        return true;
    }

    public function dec($key, $expire = 0) {
        $key = $this->buildKey($key);
        if (apc_dec($key) === false) {
            return apc_store($key, 0, $expire);
        }
        return true;
    }

    public function clear() {
        return apc_clear_cache('user');
    }

}