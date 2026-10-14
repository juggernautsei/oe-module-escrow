<?php

/**
 * Module Manager configuration entry (gear icon).
 *
 * Escrow uses core AR tables and does not require encrypted secrets yet.
 * This page is a placeholder for future module-local configuration.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Core\Header;
use OpenEMR\Core\ModulesClassLoader;
use OpenEMR\Modules\Escrow\ModulePath;

require_once __DIR__ . '/src/ModulePath.php';

if (empty($GLOBALS['fileroot'])) {
    require_once ModulePath::globalsPath();
}

$classLoader = new ModulesClassLoader($GLOBALS['fileroot']);
$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\Escrow\\',
    ModulePath::moduleRoot() . DIRECTORY_SEPARATOR . 'src'
);

$module_config = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php Header::setupHeader(); ?>
    <title><?php echo xlt('Escrow Module Configuration'); ?></title>
</head>
<body class="body_top">
<div class="container mt-3">
    <h2><?php echo xlt('Escrow Module'); ?></h2>
    <p class="text-muted">
        <?php echo xlt('No module-specific configuration is required. Escrow reads and writes core AR session and activity tables.'); ?>
    </p>
</div>
</body>
</html>
