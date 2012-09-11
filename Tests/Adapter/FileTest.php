<?php

namespace RickySu\TagcacheBundle\Tests\Adapter;

use RickySu\TagcacheBundle\Tests\Adapter\BaseTagcacheAdapter;
use RickySu\TagcacheBundle\Adapter\File;

class FileTest extends BaseTagcacheAdapter
{
    protected function setupCache()
    {
        $this->Cache = new File(md5(microtime() . rand()), array(
                    'hashkey' => true,
                    'cache_dir' => PROJECT_BASE . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'RickySu.TagcacheBundle',
                ));
    }

}
