<?php

namespace RickySu\TagcacheBundle\Tests\Adapter;

use RickySu\TagcacheBundle\Tests\Adapter\BaseTagcacheAdapter;
use RickySu\TagcacheBundle\Adapter\Memcache;

class MemcacheTest extends BaseTagcacheAdapter
{
    protected function setupCache($EnableLargeObject=false)
    {
        $this->Cache = new Memcache(md5(microtime() . rand()), array(
                    'hashkey' => true,
                    'servers' => array('127.0.0.1:11211:10'),
                    'enable_largeobject'=>$EnableLargeObject,
                ));
    }

    public function testBigObject()
    {
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
