<?php

namespace app\_interface;

interface Storage
{
    /**
     * @return mixed
     * connection or error
     */
    public static function get_connect();
}