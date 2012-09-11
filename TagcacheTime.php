<?php

namespace RickySu\TagcacheBundle;

class TagcacheTime
{
    protected function __construct()
    {
    }

    public static function time()
    {
        return microtime(true);
    }

}
