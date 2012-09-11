<?php

namespace RickySu\TagcacheBundle\Tests\Adapter;

use RickySu\TagcacheBundle\Tests\Adapter\BaseTagcacheAdapter;
use RickySu\TagcacheBundle\Adapter\Sqlite;

class SqliteTest extends BaseTagcacheAdapter
{
    protected function setupCache()
    {
        $this->Cache = new Sqlite(md5(microtime() . rand()), array(
                    'hashkey' => false,
                    'cache_dir' => PROJECT_BASE . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'RickySu.TagcacheBundle',
                ));
    }

}
