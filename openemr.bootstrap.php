<?php

/**
 * Escrow module bootstrap entry point.
 *
 * Loaded by OpenEMR ModulesApplication when the module is enabled.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Escrow;

/**
 * @global OpenEMR\Core\ModulesClassLoader $classLoader
 */
$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\Escrow\\',
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);

/**
 * @global \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $eventDispatcher
 */
$dispatcher = $eventDispatcher ?? null;
if ($dispatcher === null && !empty($GLOBALS['kernel']) && method_exists($GLOBALS['kernel'], 'getEventDispatcher')) {
    $dispatcher = $GLOBALS['kernel']->getEventDispatcher();
}

if ($dispatcher instanceof \Symfony\Component\EventDispatcher\EventDispatcherInterface) {
    $bootstrap = new Bootstrap($dispatcher, $GLOBALS['kernel'] ?? null);
    $bootstrap->subscribeToEvents();
}
