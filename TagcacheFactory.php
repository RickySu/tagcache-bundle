<?php

namespace RickySu\TagcacheBundle;

use RickySu\Tagcache\TagcacheFactory as CacheFactory;

/**
 * Class TagcacheFactory
 * @package RickySu\TagcacheBundle
 */
class TagcacheFactory
{
    /**
     * @var null
     */
    protected static $Instance = null;

    /**
     * TagcacheFactory constructor.
     */
    protected function __construct()
    {
    }

    /**
     * @param $Config
     *
     * @return null
     */
    public static function getInstance($Config)
    {
        if (!self::$Instance) {
            self::$Instance = CacheFactory::factory($Config);
        }
        return self::$Instance;
    }

}
