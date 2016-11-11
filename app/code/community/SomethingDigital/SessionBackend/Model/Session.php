<?php

use SomethingDigital_SessionBackend_Exception_ConfigurationException as ConfigurationException;

class SomethingDigital_SessionBackend_Model_Session extends Mage_Core_Model_Session
{
    const XML_NODE_SESSION_BACKEND = 'global/session/backend';

    /**
     * Generated session save method (by handler)
     *
     * @var string|false|null
     */
    protected static $sessionSaveMethod = false;

    /**
     * Override to ensure path is treated as a string.
     *
     * Otherwise, call_user_func() fails with an error for 'user' types after PHP 5.3.0+.
     *
     * @return string
     */
    public function getSessionSavePath()
    {
        // Result cannot be a SimpleXML node, must be a string.
        return (string) parent::getSessionSavePath();
    }

    /**
     * Allow a backend class to override the session save method.
     *
     * If the session save method is 'class', registers the class handler.
     *
     * @return string|null
     */
    public function getSessionSaveMethod()
    {
        // Since we have to rewrite for the path, let's allow an override.
        $method = (string) parent::getSessionSaveMethod();
        if ($method === 'class') {
            $method = $this->getDynamicSessionSaveMethod();
        }
        return $method;
    }

    /**
     * Retrieve the configured session backend class name, if any.
     *
     * @return string|null
     */
    public function getSessionSaveClass()
    {
        if (Mage::isInstalled() && $className = Mage::getConfig()->getNode(static::XML_NODE_SESSION_BACKEND)) {
            $className = (string) $className;

            // Allow using a model alias.
            if (strpos($className, '/') !== false) {
                return Mage::getConfig()->getModelClassName($className);
            }
            return $className;
        }

        return null;
    }

    /**
     * Register and retrieve the save method, or return the cached method.
     *
     * @throws ConfigurationException On bad configuration.
     * @return null|string
     */
    protected function getDynamicSessionSaveMethod()
    {
        // Only call the static registration method once.
        if (self::$sessionSaveMethod === false) {
            $class = $this->getSessionSaveClass();
            if (!$class) {
                throw new ConfigurationException('Session backend not configured.');
            }
            self::$sessionSaveMethod = call_user_func([$class, 'register']);
        }

        return self::$sessionSaveMethod;
    }
}
