<?php

namespace RickySu\TagCacheBundle;

use RickySu\TagCacheBundle\TagCahe\TagCacheAdapter;

class TagCacheFactory
{
    protected static $Instance = null;

    protected function __construct()
    {
    }

    protected static function Factory($Config)
    {
        $Driver='RickySu\\TagCacheBundle\\Adapter\\'.$Config['driver'];
        self::$Instance=new $Driver($Config['namespace'],$Config['options']);

        return self::$Instance;
    }

    /**
     * Get TagCache Instance
     * @return sfTagCacheAdapter
     */
    public static function getInstance($Config)
    {
        if (self::$Instance instanceof TagCacheAdapter) {
            return self::$Instance;
        }

        return self::Factory($Config);

    }

}
