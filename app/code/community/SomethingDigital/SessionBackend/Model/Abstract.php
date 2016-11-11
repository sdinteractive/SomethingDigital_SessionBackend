<?php

abstract class SomethingDigital_SessionBackend_Model_Abstract extends SessionHandler
{
    /**
     * Registration entry point for the save handler funcs.
     *
     * @return string|null
     */
    public static function register()
    {
        $instance = new static();
        session_set_save_handler($instance, true);

        // Our overrides will be called, but the defaults will be file storage.
        return null;
    }
}
