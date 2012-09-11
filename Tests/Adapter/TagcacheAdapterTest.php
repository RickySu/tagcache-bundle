<?php

namespace RickySu\TagcacheBundle\Tests\Adapter;

use RickySu\TagcacheBundle\Adapter\TagcacheAdapter;
use RickySu\TagcacheBundle\TagcacheObj;

class TestingTagcacheAdapter extends TagcacheAdapter
{
    public $CurrentTimestamp = null;
    public $TagUpdateTimestamp = null;

    public function __construct($Namespace, $Options)
    {
        parent::__construct($Namespace, $Options);
    }

    public function getNamespace()
    {
        return $this->Namespace;
    }

    public function getOptions()
    {
        return $this->Options;
    }

    public function getbuildKey($Key)
    {
        return $this->buildKey($Key);
    }

    protected function getCurrentTimestamp()
    {
        return $this->CurrentTimestamp;
    }

    public function getTagUpdateTimestamp($Tag)
    {
        return (isset($this->TagUpdateTimestamp[$Tag]) ? $this->TagUpdateTimestamp[$Tag] : false);
    }

    public function getTags($Key)
    {
    }

    public function getLock($Key, $LockExpire = 5)
    {
    }

    public function releaseLock($Key)
    {
    }

    public function set($key, $var, $Tags = array(), $expire = null)
    {
    }

    public function get($key)
    {
    }

    public function delete($key)
    {
    }

    public function deleteTag($Tag)
    {
    }

    public function setRaw($key, $Obj, $expire)
    {
    }

    public function getRaw($key)
    {
    }

    public function deleteRaw($key)
    {
    }

    public function clear()
    {
    }

}

class TagcacheAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $TestingTagcacheAdapter;
    protected $Namespace = null;
    protected $Options = null;

    public function DataProvider___construct()
    {
        return array(
            array(
                md5(microtime() . rand()),
                array('foo' => 'bar')
            ),
        );
    }

    public function DataProvider_testbuildKey()
    {
        $Namespace = md5(microtime() . rand());
        $Key = md5(microtime() . rand());

        return array(
            array(
                $Namespace,
                array('hashkey' => false),
                $Key,
                "$Namespace:$Key",
                "test hashKey:false"
            ),
            array(
                $Namespace,
                array('hashkey' => true),
                $Key,
                md5("$Namespace:$Key"),
                "test hashKey:true"
            ),
        );
    }

    public function DataProvider_getTagcacheObjContent()
    {
        $TagObjectString = md5(microtime() . rand());
        $TagObjectNoTag = new TagcacheObj(md5(microtime() . rand()), array(), time() + 10);
        $TagObjectWithTag = new TagcacheObj(md5(microtime() . rand()), array('TagA', 'TagB'));

        return array(
            array($TagObjectString, $TagObjectString, time(), array()),
            array($TagObjectNoTag->Var, $TagObjectNoTag, time(), array()), //Not Expired
            array(false, $TagObjectNoTag, time() + 30, array()), //Expired
            array($TagObjectWithTag->Var, $TagObjectWithTag, null, array(
                    'TagA' => time() - 10,
                    'TagB' => time() - 10,
            )), //not Expired
            array(false, $TagObjectWithTag, null, array(
                    'TagA' => time() + 1,
                    'TagB' => time(),
            )), //Expired
            array(false, $TagObjectWithTag, null, array(
                    'TagA' => time() + 1,
                    'TagB' => time() + 2,
            )), //Expired
            array(false, $TagObjectWithTag, null, array(
                    'TagB' => time() - 10,
            )), //Expired
        );
    }

    protected function setUp()
    {
    }

    /**
     *
     * @dataProvider DataProvider___construct
     */
    public function test__construct($Namespace, $Options)
    {
        $TestingTagcacheAdapter = new TestingTagcacheAdapter($Namespace, $Options);
        $this->assertEquals($Namespace, $TestingTagcacheAdapter->getNamespace());
        $this->assertEquals($Options, $TestingTagcacheAdapter->getOptions());
    }

    /**
     *
     * @dataProvider DataProvider_testbuildKey
     */
    public function testbuildKey($Namespace, $Options, $Key, $Expeted, $Message)
    {
        $TestingTagcacheAdapter = new TestingTagcacheAdapter($Namespace, $Options);
        $this->assertEquals($Expeted, $TestingTagcacheAdapter->getbuildKey($Key), $Message);
    }

    protected function tearDown()
    {
    }

}
