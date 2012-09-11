<?php

namespace RickySu\TagcacheBundle;

use RickySu\TagcacheBundle\TagCahe\TagcacheAdapter;

class TagcacheFactory
{
    protected static $Instance = null;

    protected function __construct()
    {
    }

    protected static function factory($Config)
    {
        $Driver='RickySu\\TagcacheBundle\\Adapter\\'.$Config['driver'];
        self::$Instance=new $Driver($Config['namespace'],$Config['options']);

        return self::$Instance;
    }

    /**
     * Get Tagcache Instance
     * @return sfTagcacheAdapter
     */
    public static function getInstance($Config)
    {
        if (self::$Instance instanceof TagcacheAdapter) {
            return self::$Instance;
        }

        return self::factory($Config);

    }

}
