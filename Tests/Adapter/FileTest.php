<?php

namespace Ricky\TagCacheBundle\Tests\Adapter;

use Ricky\TagCacheBundle\Tests\Adapter\BaseTagCacheAdapter;
use Ricky\TagCacheBundle\Adapter\File;

class FileTest extends BaseTagCacheAdapter {

    protected function setupCache() {
        $this->Cache = new File(md5(microtime() . rand()), array(
                    'hashkey' => true,
                    'cache_dir' => $_SERVER['SYMFONY'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'Ricky.TagCacheBundle',
                ));
    }

}