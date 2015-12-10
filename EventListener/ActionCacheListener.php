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

/**
 * Class ActionCacheListener
 * @package RickySu\TagcacheBundle\EventListener
 */
class ActionCacheListener
{
    /**
     *
     */
    const TAG_VIEW_CACHE = 'Tag:View';

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     *
     * @var \RickySu\TagcacheBundle\Adapter\TagcacheAdapter
     */
    protected $tagcache;
    /**
     * @var null
     */
    protected $reader = null;
    /**
     * @var null
     */
    protected $resolver = null;
    /**
     * @var
     */
    protected $config;

    /**
     * ActionCacheListener constructor.
     *
     * @param ContainerInterface $container
     * @param                    $resolver
     * @param                    $config
     */
    public function __construct(ContainerInterface $container, $resolver, $config)
    {
        $this->container = $container;
        $this->tagcache = $container->get('tagcache');
        $this->reader = $container->get('annotation_reader');
        $this->resolver = $resolver;
        $this->config = $config;
    }

    /**
     * @param $event
     *
     * @return array
     */
    protected function loadAnnotationConfig($event)
    {
        if (!($controller = $this->resolver->getController($event->getRequest()))) {
            return array();
        }
        $object = new \ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);
        foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
            if ($configuration instanceof TagcacheConfigurationAnnotation) {
                $tagcacheConfig = $event->getRequest()->attributes->get('tagcache');
                $annotationConfig = $configuration->getConfigs();
                if (!is_array($tagcacheConfig)) {
                    $tagcacheConfig = array();
                }
                $tagcacheConfig = array_merge($annotationConfig, $tagcacheConfig);
                if (!isset($annotationConfig['cache']) || $annotationConfig['cache'] != true) {
                    $tagcacheConfig = array();
                }
                $event->getRequest()->attributes->set('tagcache', $tagcacheConfig);

                return $tagcacheConfig;
            }
        }

        return array();
    }

    /**
     * @param $event
     *
     * @return array|bool
     */
    protected function getTagcacheConfig($event)
    {
        $defaultConfig = array(
            'cache' => true,
            'tags' => array(),
            'expires' => 600,
        );
        if (is_array($tagcacheConfig = $event->getRequest()->attributes->get('tagcache'))) {
            $tagcacheConfig = array_merge($defaultConfig, $tagcacheConfig);
        } else {
            $tagcacheConfig = array('cache' => false);
        }
        $tagcacheConfig = array_merge($tagcacheConfig, $this->loadAnnotationConfig($event));
        if (!$tagcacheConfig['cache']) {
            return false;
        }
        if (!isset($tagcacheConfig['key'])) {
            $tagcacheConfig['key'] = $event->getRequest()->attributes->get('_controller');
        }

        return $tagcacheConfig;
    }

    /**
     * Handles the event when notified or filtered.
     *
     * @param GetResponseEvent $event
     */
    public function handleRequest(GetResponseEvent $event)
    {
        if (!($tagcacheConfig = $this->getTagcacheConfig($event))) {
            return;
        }
        if (is_array($cacheContent = $this->tagcache->get($tagcacheConfig['key']))) {
            if ($cacheContent['Expires'] - (time() - $cacheContent['CreatedAt']) < 0) {
                return;
            }
            $event->setResponse(new TagcacheResponse($this->renderCacheContent($cacheContent)));

            return;
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function handleResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            if ($this->config['debug']) {
                $this->injectAssets($event->getResponse());
            }
        }
        if (!($tagcacheConfig = $this->getTagcacheConfig($event))) {
            return;
        }
        if (!($event->getResponse() instanceof TagcacheResponse)) {
            if (!$event->getResponse()->isOk()) {
                return;
            }
            $tags = is_array($tagcacheConfig['tags']) ? $tagcacheConfig['tags'] : array();
            array_push($tags, $this->buildViewCacheTag());
            $cacheContent = array(
                'Key' => $tagcacheConfig['key'],
                'Expires' => $tagcacheConfig['expires'],
                'Tags' => $tags,
                'CreatedAt' => time(),
                'Content' => $event->getResponse()->getContent(),
                'Controller' => $event->getRequest()->get('_controller'),
            );
            $this->tagcache->set(
                $tagcacheConfig['key'],
                $cacheContent,
                $cacheContent['Tags'],
                $tagcacheConfig['expires']
            );
            $event->getResponse()->setContent($this->renderCacheContent($cacheContent, false));
        }
    }

    /**
     * @return string
     */
    protected function buildViewCacheTag()
    {
        return self::TAG_VIEW_CACHE . ':' . ($this->config['debug'] ? 'dev' : 'prod');
    }

    /**
     * @param           $cacheContent
     * @param bool|true $cacheHit
     *
     * @return mixed
     */
    protected function renderCacheContent($cacheContent, $cacheHit = true)
    {
        if (!$this->config['debug']) {
            return $cacheContent['Content'];
        }
        $cacheContent['lastmodify'] = time() - $cacheContent['CreatedAt'];
        $Twig = $this->container->get('twig');

        return $Twig->render(
            'TagcacheBundle:html:tagcache_debug_panel.html.twig',
            array('CacheHit' => $cacheHit, 'CacheContent' => $cacheContent, 'uniqueid' => md5(microtime() . rand()))
        );
    }

    /**
     * @param Response $response
     */
    protected function injectAssets(Response $response)
    {
        $Twig = $this->container->get('twig');
        $content = $response->getContent();
        if (function_exists('mb_stripos')) {
            $posrFunction = 'mb_strripos';
            $posFunction = 'mb_stripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction = 'strripos';
            $posFunction = 'stripos';
            $substrFunction = 'substr';
        }
        $pos = $posrFunction($content, '</head>');
        if (false !== $pos) {
            $css = $Twig->render('TagcacheBundle:css:tagcache_debug_css.html.twig');
            $js = $Twig->render('TagcacheBundle:js:tagcache_debug_js.html.twig');
            $content = $substrFunction($content, 0, $pos) . $css . $js . $substrFunction($content, $pos);
            $response->setContent($content);
        }
    }
}
