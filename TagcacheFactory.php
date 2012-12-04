<?php

namespace RickySu\TagcacheBundle;

use RickySu\Tagcache\TagcacheFactory as CacheFactory;

class TagcacheFactory
{
    protected static $Instance = null;

    protected function __construct()
    {
    }

    public static function getInstance($Config)
    {
        if(!self::$Instance){
            self::$Instance=CacheFactory::factory($Config);
        }
        return self::$Instance;
    }

}
