<?php

namespace Ricky\TagCacheBundle\Tests\Adapter;

use Ricky\TagCacheBundle\Tests\Adapter\BaseTagCacheAdapter;
use Ricky\TagCacheBundle\Adapter\Sqlite;

class SqliteTest extends BaseTagCacheAdapter {

    protected function setupCache() {
        $this->Cache = new Sqlite(md5(microtime() . rand()), array(
                    'hashkey' => false,
                    'cache_dir' => $_SERVER['SYMFONY'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'Ricky.TagCacheBundle',
                ));
    }

}