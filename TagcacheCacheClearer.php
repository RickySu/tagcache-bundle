<?php

namespace RickySu\TagcacheBundle;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TagcacheCacheClearer implements CacheClearerInterface
{
    protected $Container,$Config;

    public function __construct(ContainerInterface $Container,$Config)
    {
        $this->Container=$Container;
        $this->Config=$Config;
    }
    public function clear($cacheDir)
    {
        $TagCache=$this->Container->get('tagcache');
        $TagCache->deleteTag('Tag:View'.':'.($this->Config['debug']?'Dev':'Prod'));
    }

}
