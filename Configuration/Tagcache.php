<?php

namespace RickySu\TagcacheBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * The Cache class handles the @Cache annotation parts.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @Annotation
 */
class Tagcache extends ConfigurationAnnotation
{
    /**
     * The expiration date as a valid date for the strtotime() function.
     *
     * @var string
     */
    protected $expires;

    /**
     * The number of seconds that the response is considered fresh by a private
     * cache like a web browser.
     *
     * @var integer
     */
    protected $maxage;

    /**
     * The number of seconds that the response is considered fresh by a public
     * cache like a reverse proxy cache.
     *
     * @var integer
     */
    protected $smaxage;

    /**
     * Whether or not the response is public or not.
     *
     * @var integer
     */
    protected $public;

    /**
     * @var
     */
    protected $cachekey;

    /**
     * @var
     */
    protected $tags;

    /**
     * @var
     */
    protected $EnableCache;

    /**
     * @param $Enable
     */
    public function setCache($Enable)
    {
        $this->EnableCache = $Enable;
    }

    /**
     * @return mixed
     */
    public function getCache()
    {
        return $this->EnableCache;
    }

    /**
     * @param $cachekey
     */
    public function setKey($cachekey)
    {
        $this->cachekey = $cachekey;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->cachekey;
    }

    /**
     * @param $tags
     */
    public function setTags($tags)
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
            'key' => $this->cachekey,
            'expires' => $this->expires,
            'tags' => $this->tags,
            'cache' => $this->EnableCache,
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

}
