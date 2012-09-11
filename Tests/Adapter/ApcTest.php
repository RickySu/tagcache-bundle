<?php

namespace RickySu\TagCacheBundle\Tests\Adapter;

use RickySu\TagCacheBundle\Tests\Adapter\BaseTagCacheAdapter;
use RickySu\TagCacheBundle\Adapter\Apc;

class ApcTest extends BaseTagCacheAdapter {

    protected function setupCache($EnableLargeObject=false) {
        $this->Cache = new Apc(md5(microtime() . rand()), array(
                    'cache_dir' => PROJECT_BASE . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'RickySu.TagCacheBundle',
                    'hashkey' => true,
                    'enable_largeobject'=>$EnableLargeObject,
                ));
    }

    public function testBigObject() {
        $this->setupCache(true);
        $BigString = '';
        for ($i = 0; $i < 1024 * 1024 * 4; $i++) {
            $BigString .= rand(0,9);
        }
        $Hash=md5($BigString);
        $this->assertEquals(1024 * 1024 * 4, strlen($BigString));
        $this->Cache->set('TestBigString',$BigString);
        $this->assertEquals($Hash,md5($this->Cache->get('TestBigString')));
        $this->setupCache(false);
    }

}