<?php

namespace RickySu\TagCacheBundle\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;
use \PDO;

class Sqlite extends TagCacheAdapter
{
    protected $Sqlite, $DBFile;

    public function __construct($NameSpace, $Options)
    {
        parent::__construct($NameSpace, $Options);
        $this->InitDBFile();
    }

    protected function InitDBFile()
    {
        $this->DBFile = $this->Options['cache_dir'] . DIRECTORY_SEPARATOR . 'Sqlite' . DIRECTORY_SEPARATOR . md5($this->Namespace) . '.sqlite';
        if (file_exists($this->DBFile)) {
            $this->Sqlite = new PDO("sqlite:" . $this->DBFile);

            return;
        }
        @mkdir(dirname($this->DBFile), 0777, true);
        $this->Sqlite = new PDO("sqlite:" . $this->DBFile);
        foreach (array('CacheData.sql', 'TagRelation.sql', 'Tag.sql') as $File) {
            $sql = file_get_contents(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . $File));
            $this->Sqlite->exec($sql);
        }
    }

    public function getTagUpdateTimestamp($Tag)
    {
        $TagHash = md5($Tag);
        $sql = "select * from Tag where tagkey='$TagHash';";
        $Res = $this->Sqlite->query($sql);
        $Row = $Res->fetch(PDO::FETCH_ASSOC);

        return $Row['createdate'];
    }

    public function getTags($Key)
    {
        $KeyHash = md5($Key);
        $sql = "select * from CacheData where cachekey='$KeyHash';";
        $Res = $this->Sqlite->query($sql);
        $Row = $Res->fetch(PDO::FETCH_ASSOC);
        $Obj = unserialize($Row['cachedata']);
        if ($Obj instanceof TagCacheObj) {
            return $Obj->Tags;
        }

        return false;
    }

    public function set($Key, $var, $Tags = array(), $expire = null)
    {
        if ($expire) {
            $expire+=time();
        }
        $KeyHash = md5($Key);
        $this->Sqlite->beginTransaction();
        if (is_array($Tags)) {
            foreach ($Tags as $Tag) {
                $TagHash = md5($Tag);
                $sql = "delete from TagRelation where tagkey='$TagHash' and cachekey='$KeyHash';";
                $this->Sqlite->exec($sql);
                $sql = "insert into TagRelation(tagkey,cachekey) values('$TagHash','$KeyHash');";
                $this->Sqlite->exec($sql);
                $sql = "insert into Tag(tagkey,createdate) values('$TagHash','" . time() . "');";
                $this->Sqlite->exec($sql);
            }
        }
        $Obj = new TagCacheObj($var, $Tags, $expire);
        $sql = "delete from CacheData where cachekey='$KeyHash';";
        $this->Sqlite->exec($sql);
        $sql = "insert into CacheData(cachekey,cachedata,createdate) values(?,?,?);";
        $this->Sqlite->prepare($sql)->execute(array($KeyHash, serialize($Obj), time()));
        $this->Sqlite->commit();

        return true;
    }

    public function get($Key)
    {
        $KeyHash = md5($Key);
        $sql = "select * from CacheData where cachekey='$KeyHash';";
        $Res = $this->Sqlite->query($sql);
        $Row = $Res->fetch(PDO::FETCH_ASSOC);
        $Obj = unserialize($Row['cachedata']);
        if ($Obj instanceof TagCacheObj) {
            $Data = $Obj->getVar($this);
            if ($Data === false) {
                $this->delete($Key);
            }

            return $Data;
        }

        return $Obj;
    }

    public function delete($Key)
    {
        $KeyHash = md5($Key);
        $sql = "delete from CacheData where cachekey='$KeyHash';";
        $this->Sqlite->exec($sql);

        return true;
    }

    public function deleteTag($Tag)
    {
        $TagHash = md5($Tag);
        $this->Sqlite->beginTransaction();
        $sql = "delete from CacheData where cachekey in (select cachekey from TagRelation where tagkey='$TagHash');";
        $this->Sqlite->exec($sql);
        $sql = "delete from TagRelation where tagkey='$TagHash';";
        $this->Sqlite->exec($sql);
        $sql = "delete from Tag where tagkey='$TagHash';";
        $this->Sqlite->exec($sql);
        $this->Sqlite->commit();

        return true;
    }

    public function clear()
    {
        $this->Sqlite->beginTransaction();
        $sql = "delete from CacheData;";
        $this->Sqlite->exec($sql);
        $sql = "delete from TagRelation;";
        $this->Sqlite->exec($sql);
        $sql = "delete from Tag;";
        $this->Sqlite->exec($sql);
        $this->Sqlite->commit();
    }

    public function getRaw($key)
    {
    }

    public function setRaw($key, $Obj, $expire)
    {
    }

    public function deleteRaw($key)
    {
    }

}
