<?php

namespace RickySu\TagCacheBundle\Tests\Adapter;

use RickySu\TagCacheBundle\Adapter\TagCacheAdapter;
use RickySu\TagCacheBundle\TagCacheObj;

class TestingTagCacheAdapter extends TagCacheAdapter {

    public $CurrentTimestamp=null;
    public $TagUpdateTimestamp=null;
    public function __construct($Namespace, $Options) {
        parent::__construct($Namespace, $Options);
    }

    public function getNamespace() {
        return $this->Namespace;
    }

    public function getOptions() {
        return $this->Options;
    }

    public function getbuildKey($Key) {
        return $this->buildKey($Key);
    }

    public function getTagCacheObjContent($TagCacheObj) {
        return parent::getTagCacheObjContent($TagCacheObj);
    }

    protected function getCurrentTimestamp(){
        return $this->CurrentTimestamp;
    }

    public function getTagUpdateTimestamp($Tag) {
        return (isset($this->TagUpdateTimestamp[$Tag])?$this->TagUpdateTimestamp[$Tag]:false);
    }

    public function getTags($Key) {

    }

    public function getLock($Key, $LockExpire = 5) {

    }

    public function releaseLock($Key) {

    }

    public function set($key, $var, $Tags=array(), $expire=null) {

    }

    public function get($key) {

    }

    public function delete($key) {

    }

    public function TagDelete($Tag) {

    }

    public function clear() {

    }

}

class TagCacheAdapterTest extends \PHPUnit_Framework_TestCase {

    protected $TestingTagCacheAdapter;
    protected $Namespace = null;
    protected $Options = null;

    public function DataProvider___construct() {
        return array(
            array(
                md5(microtime() . rand()),
                array('foo' => 'bar')
            ),
        );
    }

    public function DataProvider_testbuildKey() {
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

    public function DataProvider_getTagCacheObjContent(){
        $TagObjectString=md5(microtime().rand());
        $TagObjectNoTag=new TagCacheObj(md5(microtime().rand()),array(),time()+10);
        $TagObjectWithTag=new TagCacheObj(md5(microtime().rand()),array('TagA','TagB'));
        return array(
            array($TagObjectString,$TagObjectString,time(),array()),
            array($TagObjectNoTag->Var,$TagObjectNoTag,time(),array()),  //Not Expired
            array(false,$TagObjectNoTag,time()+30,array()),              //Expired
            array($TagObjectWithTag->Var,$TagObjectWithTag,null,array(
                'TagA'=>time()-10,
                'TagB'=>time()-10,
            )),              //not Expired
            array(false,$TagObjectWithTag,null,array(
                'TagA'=>time()+1,
                'TagB'=>time(),
            )),              //Expired
            array(false,$TagObjectWithTag,null,array(
                'TagA'=>time()+1,
                'TagB'=>time()+2,
            )),              //Expired
            array(false,$TagObjectWithTag,null,array(
                'TagB'=>time()-10,
            )),              //Expired
        );
    }

    protected function setUp() {

    }

    /**
     *
     * @dataProvider DataProvider___construct
     */
    public function test__construct($Namespace, $Options) {
        $TestingTagCacheAdapter = new TestingTagCacheAdapter($Namespace, $Options);
        $this->assertEquals($Namespace, $TestingTagCacheAdapter->getNamespace());
        $this->assertEquals($Options, $TestingTagCacheAdapter->getOptions());
    }

    /**
     *
     * @dataProvider DataProvider_testbuildKey
     */
    public function testbuildKey($Namespace, $Options, $Key, $Expeted, $Message) {
        $TestingTagCacheAdapter = new TestingTagCacheAdapter($Namespace, $Options);
        $this->assertEquals($Expeted, $TestingTagCacheAdapter->getbuildKey($Key), $Message);
    }

    /**
     *
     * @dataProvider DataProvider_getTagCacheObjContent
     */
    public function testgetTagCacheObjContent($Expected,$TagCacheObj,$Timestamp,$Tags) {
        $Namespace = md5(microtime() . rand());
        $TestingTagCacheAdapter = new TestingTagCacheAdapter($Namespace, array('hashkey' => false));
        $TestingTagCacheAdapter->CurrentTimestamp=$Timestamp;
        $TestingTagCacheAdapter->TagUpdateTimestamp=$Tags;
        $this->assertEquals($Expected,$TestingTagCacheAdapter->getTagCacheObjContent($TagCacheObj));
    }

    protected function tearDown() {

    }

}