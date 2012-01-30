<?php

namespace Ricky\TagCacheBundle;

use Ricky\TagCacheBundle\TagCahe\TagCacheAdapter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class TagCacheFactory {

    protected static $Instance = null;

    protected function __construct() {

    }

    static protected function Factory($Config){
        $Driver='Ricky\\TagCacheBundle\\Adapter\\'.$Config['driver'];
        self::$Instance=new $Driver($Config['namespace'],$Config['options']);
        return self::$Instance;
    }

    /**
     * Get TagCache Instance
     * @return sfTagCacheAdapter
     */
    static public function getInstance($Config) {

        if (self::$Instance instanceof TagCacheAdapter) {
            return self::$Instance;
        }
        return self::Factory($Config);

    }

}