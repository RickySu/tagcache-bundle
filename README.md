TagcacheBundle
==============

Introduction
------------

This bundle provides cache with tags.

Features
------------

* Stores a cache with multiple tags. And deletes cache by using tag.
* Provides controller cache.

Requirements
------------

* Annotations for Controllers.

Installation
------------

editing the composer.json file in the root project.

### Editing the composer.json under require: {} section add

```
"rickysu/tagcache-bundle": "0.1.*",
```
### Update Bundle :

```
php composer.phar update
```

### Instantiate Bundle :

```php
<?php
//app/AppKernel.hpp
public function registerBundles()
{
   $bundles = array(
        // ...
        new RickySu\TagcacheBundle\TagcacheBundle(),
   );
}
```

### Controller Cache

#### Controller Setting
```php
<?php
//in Controller
namespace Acme\DemoBundle\Controller;

// these import the "@Tagcache" annotations
use RickySu\TagcacheBundle\Configuration\Tagcache;

class DemoController extends Controller
{
    /**
     * @Route("/hello/{name}", name="_demo_hello")
     * @Tagcache(expires=600,cache=true)
     * @Template()
     */
    public function helloAction($name)
    {
        return array('name' => $name);
    }

    /**
     * @Route("/test", name="_demo_test")
     * @Tagcache(expires=600,tags={"TagA","TagB"},cachekey=custom_cache_key,cache=true)
     * @Template()
     */
    public function testAction()
    {
        return;
    }
}
```

#### View Setting(Twig)

```html+jinja
{#in view render a controller#}
{%render 'AcmeDemoBundle:Demo:test' with {
     'tagcache':    {
           'key':    'custom_cache_key',
           'tags':   {'TagC','TagD'},
           'expires': 300
     }
}%}
```

#### Note

If you both define cache params in view and controller. _Tagcache in view will overwrite controller annotations.
But remember,controller annotation config "cache" must set to true,If you want to turn on controller cache.

TODO
----


LICENSE
-------

MIT