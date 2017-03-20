<?php

namespace RickySu\TagcacheBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class Tagcache extends ConfigurationAnnotation
{
    protected $key;

    protected $tags = array();

    protected $cache = true;

    protected $expires;

    protected $express;

    /**
     * @param $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns the expiration date for the Expires header field.
     *
     * @return string
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Sets the expiration date for the Expires header field.
     *
     * @param string $expires A valid php date
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;
    }

    /**
     * Returns the annotation alias name.
     *
     * @return string
     * @see ConfigurationInterface
     */
    public function getAliasName()
    {
        return 'tagcache';
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return array(
            'key' => $this->key,
            'expires' => $this->expires,
            'tags' => $this->tags,
            'cache' => $this->cache,
        );
    }

    /**
     * Only one cache directive is allowed
     *
     * @return Boolean
     * @see ConfigurationInterface
     */
    public function allowArray()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getExpress()
    {
        return $this->express;
    }

    /**
     * @param mixed $express
     */
    public function setExpress($express)
    {
        $this->express = $express;
    }

    /**
     * @return bool
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param bool $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

}
