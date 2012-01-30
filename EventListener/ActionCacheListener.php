<?php

namespace Ricky\TagCacheBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Ricky\TagCacheBundle\Component\TagCacheResponse;
use Ricky\TagCacheBundle\Configuration\TagCache as TagCacheConfigurationAnnotation;

class ActionCacheListener {

    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $Container;

    /**
     *
     * @var Ricky\TagCacheBundle\Adapter\TagCacheAdapter
     */
    protected $TagCache;
    protected $Reader = null;
    protected $Resolver = null;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The service container instance
     */
    public function __construct(ContainerInterface $container, $Resolver, $Config) {
        $this->Container = $container;
        $this->TagCache = $container->get('tag_cache');
        $this->Reader = $container->get('annotation_reader');
        $this->Resolver = $Resolver;
    }

    protected function LoadAnnotationConfig($Event) {
        if (!($Controller = $this->Resolver->getController($Event->getRequest()))) {
            return array();
        }
        $Object = new \ReflectionObject($Controller[0]);
        $Method = $Object->getMethod($Controller[1]);
        foreach ($this->Reader->getMethodAnnotations($Method) as $Configuration) {
            if ($Configuration instanceof TagCacheConfigurationAnnotation) {
                $TagCacheConfig = $Event->getRequest()->attributes->get('_TagCache');
                $AnnotationConfig = $Configuration->getConfigs();
                if (!is_array($TagCacheConfig)) {
                    $TagCacheConfig = array();
                }
                $TagCacheConfig = array_merge($AnnotationConfig, $TagCacheConfig);
                if (!isset($AnnotationConfig['cache']) || $AnnotationConfig['cache'] != true) {
                    $TagCacheConfig = array();
                }
                $Event->getRequest()->attributes->set('_TagCacheKey', $TagCacheConfig);
                return $TagCacheConfig;
            }
        }
        return array();
    }

    protected function getTagCacheConfig($Event) {
        if (!($TagCacheConfig = $Event->getRequest()->attributes->get('_TagCache'))) {
            $TagCacheConfig = $this->LoadAnnotationConfig($Event);
        }
        if (!isset($TagCacheConfig['cache']) || !$TagCacheConfig['cache']) {
            return false;
        }
        if (!isset($TagCacheConfig['key'])) {
            $TagCacheConfig['key'] = $Event->getRequest()->attributes->get('_controller');
        }
        return $TagCacheConfig;
    }

    /**
     * Handles the event when notified or filtered.
     *
     * @param Event $event
     */
    public function handleRequest(GetResponseEvent $Event) {
        if (!($TagCacheConfig = $this->getTagCacheConfig($Event))) {
            return;
        }
        if (is_array($CacheContent = $this->TagCache->get($TagCacheConfig['key']))) {
            if ($CacheContent['Expires'] - (time() - $CacheContent['CreatedAt']) < 0) {
                return;
            }
            $Event->setResponse(new TagCacheResponse($CacheContent['Content']));
            return;
        }
    }

    public function handleResponse(FilterResponseEvent $Event) {
        if (!($TagCacheConfig = $this->getTagCacheConfig($Event))) {
            return;
        }
        if (!($Event->getResponse() instanceof TagCacheResponse)) {
            $CacheContent = array(
                'Expires' => $TagCacheConfig['expires'],
                'Tags' => $TagCacheConfig['tags'],
                'CreatedAt' => time(),
                'Content' => $Event->getResponse()->getContent(),
            );
            $this->TagCache->set($TagCacheConfig['key'], $CacheContent, $TagCacheConfig['expires']);
        }
    }

}