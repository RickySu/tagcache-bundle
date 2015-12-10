<?php

namespace RickySu\TagcacheBundle;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TagcacheCacheClearer
 * @package RickySu\TagcacheBundle
 */
class TagcacheCacheClearer implements CacheClearerInterface
{
    /**
     * @var ContainerInterface
     */
    /**
     * @var ContainerInterface
     */
    protected $container, $config;

    /**
     * TagcacheCacheClearer constructor.
     *
     * @param ContainerInterface $container
     * @param                    $config
     */
    public function __construct(ContainerInterface $container, $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * @param $cacheDir
     */
    public function clear($cacheDir)
    {
        $TagCache = $this->container->get('tagcache');
        $TagCache->deleteTag('Tag:View' . ':' . ($this->config['debug'] ? 'dev' : 'prod'));
    }
}
