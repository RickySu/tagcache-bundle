<?php

namespace RickySu\TagcacheBundle\EventListener;

use Backend\BaseBundle\Security\ExpressionLanguage;
use Doctrine\Common\Annotations\Reader;
use RickySu\Tagcache\Adapter\TagcacheAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
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
     *
     * @var TagcacheAdapter
     */
    protected $tagcache;

    /**
     * @var Reader
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
    protected $twig;

    /**
     * ActionCacheListener constructor.
     *
     * @param                    $resolver
     * @param                    $config
     */
    public function __construct($tagcache, $reader, $resolver, $config, $twig)
    {
        $this->tagcache = $tagcache;
        $this->reader = $reader;
        $this->resolver = $resolver;
        $this->config = $config;
        $this->twig = $twig;
    }

    /**
     * @param $event
     *
     * @return TagcacheConfigurationAnnotation | null
     */
    protected function loadAnnotationConfig(Request $request)
    {
        if (!($controller = $this->resolver->getController($request))) {
            return null;
        }

        $object = new \ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);
        foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
            if ($configuration instanceof TagcacheConfigurationAnnotation) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * @param $event
     *
     * @return TagcacheConfigurationAnnotation|bool
     */
    protected function getTagcacheConfig(Request $request)
    {
        if(!($tagcacheConfig = $this->loadAnnotationConfig($request))){
            return null;
        }

        if (!$tagcacheConfig->getCache()) {
            return null;
        }

        if($tagcacheConfig->getExpress() !== null){
            $key = $this->parseExpressKey($tagcacheConfig->getExpress(), $request);
            $tagcacheConfig->setKey($key);
        }

        if ($tagcacheConfig->getKey() === null) {
            $tagcacheConfig->setKey($request->attributes->get('_controller'));
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
        if (!($tagcacheConfig = $this->getTagcacheConfig($event->getRequest()))) {
            return;
        }

        if (is_array($cacheContent = $this->tagcache->get($tagcacheConfig->getKey()))) {
            if ($cacheContent['Expires'] - (time() - $cacheContent['CreatedAt']) < 0) {
                return;
            }
            $response = new TagcacheResponse($this->renderCacheContent($cacheContent));
            $response->headers->add($cacheContent['Headers']);
            $event->setResponse($response);
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
        if (!($tagcacheConfig = $this->getTagcacheConfig($event->getRequest()))) {
            return;
        }

        if (!($event->getResponse() instanceof TagcacheResponse)) {
            if (!$event->getResponse()->isOk()) {
                return;
            }
            $tags = $tagcacheConfig->getTags();
            array_push($tags, $this->buildViewCacheTag());
            $cacheContent = array(
                'Key' => $tagcacheConfig->getKey(),
                'Expires' => $tagcacheConfig->getExpires(),
                'Tags' => $tags,
                'CreatedAt' => time(),
                'Content' => $event->getResponse()->getContent(),
                'Headers' => $event->getResponse()->headers->all(),
                'Controller' => $event->getRequest()->get('_controller'),
            );
            $this->tagcache->set(
                $tagcacheConfig->getKey(),
                $cacheContent,
                $cacheContent['Tags'],
                $tagcacheConfig->getExpires()
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
        if (!($this->config['debug'] && $this->twig)) {
            return $cacheContent['Content'];
        }
        $cacheContent['lastmodify'] = time() - $cacheContent['CreatedAt'];

        return $this->twig->render(
            'TagcacheBundle:html:tagcache_debug_panel.html.twig',
            array('CacheHit' => $cacheHit, 'CacheContent' => $cacheContent, 'uniqueid' => md5(microtime() . rand()))
        );
    }

    /**
     * @param Response $response
     */
    protected function injectAssets(Response $response)
    {
        $Twig = $this->twig;
        $content = $response->getContent();
        if (function_exists('mb_stripos')) {
            $posrFunction = 'mb_strripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction = 'strripos';
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

    protected function parseExpressKey($expression, Request $request)
    {
        $expressionLanguage = new ExpressionLanguage();
        return $expressionLanguage->evaluate($expression, array('request' => $request));
    }
}
