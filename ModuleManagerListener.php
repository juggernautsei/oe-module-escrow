<?php

/**
 * Laminas Module Manager action hooks for the Escrow module.
 *
 * Do not declare a namespace on this class; Module Manager instantiates it directly.
 *
 * @package   OpenEMR Modules
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Core\AbstractModuleActionListener;

class ModuleManagerListener extends AbstractModuleActionListener
{
    private const MODULE_NAMESPACE = 'OpenEMR\\Modules\\Escrow\\';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Dispatch Module Manager lifecycle actions.
     *
     * @param mixed  $methodName
     * @param mixed  $modId
     * @param string $currentActionStatus
     */
    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists(self::class, $methodName)) {
            return $this->$methodName($modId, $currentActionStatus);
        }

        return $currentActionStatus;
    }

    public static function getModuleNamespace(): string
    {
        return self::MODULE_NAMESPACE;
    }

    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    private function install($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function enable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function disable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function help_requested($modId, $currentActionStatus): mixed
    {
        if (file_exists(__DIR__ . '/show_help.php')) {
            include __DIR__ . '/show_help.php';
        }

        return $currentActionStatus;
    }

    /**
     * Clean up on unregister. Escrow has no module tables today.
     *
     * @param mixed $modId
     * @param mixed $currentActionStatus
     */
    private function unregister($modId, $currentActionStatus): mixed
    {
        // No DROP TABLE required until module-owned schema is introduced in table.sql.
        // Keep this hook so future cleanup (tables, ACL, background_services) has a home.
        return $currentActionStatus;
    }

    private function reset_module($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }
}
