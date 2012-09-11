<?php

namespace RickySu\TagCacheBundle\Tests\Adapter;

use RickySu\TagCacheBundle\Tests\Adapter\BaseTagCacheAdapter;
use RickySu\TagCacheBundle\Adapter\Sqlite;

class SqliteTest extends BaseTagCacheAdapter
{
    protected function setupCache()
    {
        $this->Cache = new Sqlite(md5(microtime() . rand()), array(
                    'hashkey' => false,
                    'cache_dir' => PROJECT_BASE . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'RickySu.TagCacheBundle',
                ));
    }

}
