<?php

use SomethingDigital_SessionBackend_Exception_ConfigurationException as ConfigurationException;

class SomethingDigital_SessionBackend_Model_Migrate extends SomethingDigital_SessionBackend_Model_Abstract
{
    const XML_NODE_SESSION_MIGRATE_FROM = 'global/session/migrate_from';
    const XML_NODE_SESSION_MIGRATE_FROM_PATH = 'global/session/migrate_from_path';
    const XML_NODE_SESSION_MIGRATE_TO = 'global/session/migrate_to';
    const XML_NODE_SESSION_MIGRATE_TO_PATH = 'global/session/migrate_to_path';

    /**
     * @var SessionHandlerInterface $from
     */
    protected $from = null;

    /**
     * @var SessionHandlerInterface $to
     */
    protected $to = null;

    /**
     * Build the migration backend and configure settings.
     *
     * @throws ConfigurationException Invalid configuration.
     */
    public function __construct()
    {
        $fromHandler = (string) Mage::getConfig()->getNode(static::XML_NODE_SESSION_MIGRATE_FROM);
        $toHandler = (string) Mage::getConfig()->getNode(static::XML_NODE_SESSION_MIGRATE_TO);
        $this->from = $this->getBackend($fromHandler);
        $this->to = $this->getBackend($toHandler);

        if ($this->from === null && $this->to === null) {
            // Not possible.
            throw new ConfigurationException('Unsupported session migration configuration.');
        } elseif ($this->from === null) {
            // This will set the default (e.g. files or memcached etc.) that we override.
            session_module_name($fromHandler);
        } elseif ($this->to === null) {
            session_module_name($toHandler);
        }
    }

    public function open($save_path, $session_name)
    {
        // $save_path is just the class name.

        $fromPath = (string) Mage::getConfig()->getNode(static::XML_NODE_SESSION_MIGRATE_FROM_PATH);
        if (!$fromPath) {
            $fromPath = Mage::getBaseDir('session');
        }
        $toPath = (string) Mage::getConfig()->getNode(static::XML_NODE_SESSION_MIGRATE_TO_PATH);
        if (!$toPath) {
            $toPath = Mage::getBaseDir('session');
        }

        $from_success = $this->from === null ? parent::open($fromPath, $session_name) : $this->from->open($fromPath, $session_name);
        $to_success = $this->to === null ? parent::open($toPath, $session_name) : $this->to->open($toPath, $session_name);

        return $from_success && $to_success;
    }

    protected function getBackend($type)
    {
        if (strpos($type, '/') !== false) {
            // Model name (might be e.g. core_resource/session.)
            return Mage::getModel($type);
        } elseif (strlen($type) > 1 && strtoupper($type[0]) == $type[0]) {
            // Class name.
            return new $type;
        } elseif ($type === 'db') {
            // We specifically don't want a rewrite here - Cm_RedisSession uses one.
            $model = Mage::getResourceModel('core/session');
            if (in_array('Cm_RedisSession_Model_Session', class_parents($model))) {
                return new Mage_Core_Model_Resource_Session();
            }
            return $model;
        } elseif ($type === 'redis_session') {
            return Mage::getResourceModel('core/session');
        } else {
            // Use the built-in handler for this name.
            // Examples: files, memcache, memcached, etc.
            return null;
        }
    }

    public function read($session_id)
    {
        // Try the destination first.
        $data = $this->to === null ? parent::read($session_id) : $this->to->read($session_id);
        // Note: let's be explicit, we don't want to coerce a string to an integer.
        if ($data === '' || $data === false || $data === null) {
            // Not found, let's look at the source.
            $data = $this->from === null ? parent::read($session_id) : $this->from->read($session_id);
            if ($data !== '' && $data !== false && $data !== null) {
                // Move the data over right away.
                $success = $this->from === null ? parent::write($session_id, $data) : $this->from->write($session_id, $data);
            } else {
                $success = false;
            }

            if ($success) {
                // Moving was successful - delete from the source so we don't have an outdated copy.
                $this->from === null ? parent::destroy($session_id) : $this->from->destroy($session_id);
            }
        }

        return $data;
    }

    public function write($session_id, $data)
    {
        // Always write to the destination.
        return $this->to === null ? parent::write($session_id, $data) : $this->to->write($session_id, $data);
    }

    public function close()
    {
        $from_success = $this->from === null ? parent::close() : $this->from->close();
        $to_success = $this->to === null ? parent::close() : $this->to->close();
        return $from_success && $to_success;
    }

    public function destroy($session_id)
    {
        $from_success = $this->from === null ? parent::destroy($session_id) : $this->from->destroy($session_id);
        $to_success = $this->to === null ? parent::destroy($session_id) : $this->to->destroy($session_id);
        return $from_success || $to_success;
    }

    public function gc($maxlifetime)
    {
        $from_success = $this->from === null ? parent::gc($maxlifetime) : $this->from->gc($maxlifetime);
        $to_success = $this->to === null ? parent::gc($maxlifetime) : $this->to->gc($maxlifetime);
        return $from_success && $to_success;
    }
}
