<?php

namespace Ricky\TagCacheBundle\Configuration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * The Cache class handles the @Cache annotation parts.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @Annotation
 */
class TagCache extends ConfigurationAnnotation
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

    protected $cachekey;

    protected $tags;

    protected $EnableCache;

    public function setCache($Enable){
        $this->EnableCache=$Enable;
    }

    public function getCache(){
        return $this->EnableCache;
    }

    public function setKey($cachekey){
        $this->cachekey=$cachekey;
    }

    public function getKey(){
        return $this->cachekey;
    }

    public function setTags($tags){
        $this->tags=$tags;
    }

    public function getTags(){
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

    public function getConfigs(){
        return array(
            'key' => $this->cachekey,
            'expires' => $this->expires,
            'tags'    => $this->tags,
            'cache'   => $this->EnableCache,
        );
    }
}
