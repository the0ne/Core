<?php
/**
 * A Horde_Injector based Horde_Share factory.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */

/**
 * A Horde_Injector based Horde_Share factory.
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Core_Factory_ShareBase extends Horde_Core_Factory_Base
{
    /**
     * Local cache of created share instances.
     *
     * @var array
     */
    protected $_instances = array();

    /**
     * Cache of session share entries.
     *
     * @var array
     */
    protected $_sessionCache = array();

    /**
     * Returns the share driver instance.
     *
     * @param string $app     The application scope of the share. If empty,
     *                        default to current application.
     * @param string $driver  The storage driver to use. If empty, use the
     *                        globally configured storage driver.
     *
     * @return Horde_Share_Base  The share driver instance.
     */
    public function create($app = null, $driver = null)
    {
        global $conf, $registry, $session;

        if (empty($driver)) {
            $driver = $conf['share']['driver'];
        }
        if (empty($app)) {
            $app = $this->_injector->getInstance('Horde_Registry')->getApp();
        }

        $sig = $app . '_' . $driver;
        if (isset($this->_instances[$sig])) {
            return $this->_instances[$sig];
        }

        $class = $this->_getDriverName($driver, 'Horde_Share');
        $ob = new $class($app, $registry->getAuth(), $this->_injector->getInstance('Horde_Perms'), $this->_injector->getInstance('Horde_Group'));
        $cb = new Horde_Core_Share_FactoryCallback($app, $driver);
        $ob->setShareCallback(array($cb, 'create'));
        $ob->setLogger($this->_injector->getInstance('Horde_Log_Logger'));

        if (!empty($conf['share']['cache'])) {
            $cache_sig = 'horde_share/' . $app . '/' . $driver;
            $listCache = $session->retrieve($cache_sig);
            $ob->setListCache($listCache);

            if (empty($this->_sessionCache)) {
                register_shutdown_function(array($this, 'shutdown'));
            }

            $this->_sessionCache[$sig] = $cache_sig;
        }

        $this->_instances[$sig] = $ob;

        return $ob;
    }

    /**
     * Shutdown function.
     */
    public function shutdown()
    {
        global $session;

        foreach ($this->_sessionCache as $sig => $cache_sig) {
            $session->store($this->_instances[$sig]->getListCache(), false, $cache_sig);
        }
    }

}
