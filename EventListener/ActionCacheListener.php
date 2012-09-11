<?php

namespace RickySu\TagcacheBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use RickySu\TagcacheBundle\Component\TagcacheResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use RickySu\TagcacheBundle\Configuration\Tagcache as TagcacheConfigurationAnnotation;

class ActionCacheListener {

    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $Container;

    /**
     *
     * @var RickySu\TagcacheBundle\Adapter\TagcacheAdapter
     */
    protected $Tagcache;
    protected $Reader = null;
    protected $Resolver = null;
    protected $Config;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The service container instance
     */
    public function __construct(ContainerInterface $container, $Resolver, $Config) {
        $this->Container = $container;
        $this->Tagcache = $container->get('tagcache');
        $this->Reader = $container->get('annotation_reader');
        $this->Resolver = $Resolver;
        $this->Config = $Config;
    }

    protected function loadAnnotationConfig($Event) {
        if (!($Controller = $this->Resolver->getController($Event->getRequest()))) {
            return array();
        }
        $Object = new \ReflectionObject($Controller[0]);
        $Method = $Object->getMethod($Controller[1]);
        foreach ($this->Reader->getMethodAnnotations($Method) as $Configuration) {
            if ($Configuration instanceof TagcacheConfigurationAnnotation) {
                $TagcacheConfig = $Event->getRequest()->attributes->get('Tagcache');
                $AnnotationConfig = $Configuration->getConfigs();
                if (!is_array($TagcacheConfig)) {
                    $TagcacheConfig = array();
                }
                $TagcacheConfig = array_merge($AnnotationConfig, $TagcacheConfig);
                if (!isset($AnnotationConfig['cache']) || $AnnotationConfig['cache'] != true) {
                    $TagcacheConfig = array();
                }
                $Event->getRequest()->attributes->set('Tagcache', $TagcacheConfig);

                return $TagcacheConfig;
            }
        }

        return array();
    }

    protected function getTagcacheConfig($Event) {
        if (!($TagcacheConfig = $Event->getRequest()->attributes->get('Tagcache'))) {
            $TagcacheConfig = $this->loadAnnotationConfig($Event);
        }
        if (!isset($TagcacheConfig['cache']) || !$TagcacheConfig['cache']) {
            return false;
        }
        if (!isset($TagcacheConfig['key'])) {
            $TagcacheConfig['key'] = $Event->getRequest()->attributes->get('_controller');
        }

        return $TagcacheConfig;
    }

    /**
     * Handles the event when notified or filtered.
     *
     * @param Event $event
     */
    public function handleRequest(GetResponseEvent $Event) {
        if (!($TagcacheConfig = $this->getTagcacheConfig($Event))) {
            return;
        }
        if (is_array($CacheContent = $this->Tagcache->get($TagcacheConfig['key']))) {
            if ($CacheContent['Expires'] - (time() - $CacheContent['CreatedAt']) < 0) {
                return;
            }
            $Event->setResponse(new TagcacheResponse($this->renderCacheContent($CacheContent)));
            return;
        }
    }

    public function handleResponse(FilterResponseEvent $Event) {
        if (HttpKernelInterface::MASTER_REQUEST === $Event->getRequestType()) {
            $this->injectAssets($Event->getResponse());
        }
        if (!($TagcacheConfig = $this->getTagcacheConfig($Event))) {
            return;
        }
        if (!($Event->getResponse() instanceof TagcacheResponse)) {
            $CacheContent = array(
                'Key' => $TagcacheConfig['key'],
                'Expires' => $TagcacheConfig['expires'],
                'Tags' => $TagcacheConfig['tags'],
                'CreatedAt' => time(),
                'Content' => $Event->getResponse()->getContent(),
                'Controller' => $Event->getRequest()->get('_controller'),
            );
            $this->Tagcache->set($TagcacheConfig['key'], $CacheContent, $TagcacheConfig['expires']);
            $Event->getResponse()->setContent($this->renderCacheContent($CacheContent, false));
        }
    }

    protected function renderCacheContent($CacheContent, $CacheHit = true) {
        if (!$this->Config['debug']) {
            return $CacheContent['Content'];
        }
        $CacheContent['lastmodify']=time()-$CacheContent['CreatedAt'];
        $Twig=$this->Container->get('twig');
        return $Twig->render('TagcacheBundle:html:tagcache_debug_panel.html.twig',array('CacheHit'=>$CacheHit,'CacheContent'=>$CacheContent,'uniqueid'=>md5(microtime().rand())));
    }

    protected function injectAssets(Response $Response){
        $Twig=$this->Container->get('twig');
        $Content=$Response->getContent();
        if (function_exists('mb_stripos')) {
            $posrFunction   = 'mb_strripos';
            $posFunction    = 'mb_stripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction   = 'strripos';
            $posFunction    = 'stripos';
            $substrFunction = 'substr';
        }
        $pos = $posrFunction($Content, '</head>');
        if (false !== $pos) {
            $CSS=$Twig->render('TagcacheBundle:css:tagcache_debug_css.html.twig');
            $JS=$Twig->render('TagcacheBundle:js:tagcache_debug_js.html.twig');
            $Content = $substrFunction($Content, 0, $pos).$CSS.$JS.$substrFunction($Content, $pos);
            $Response->setContent($Content);
        }

    }
}
