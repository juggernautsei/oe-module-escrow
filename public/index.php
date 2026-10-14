<?php

/**
 * Escrow report public entry point.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once dirname(__DIR__, 4) . '/globals.php';

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;
use OpenEMR\Core\Kernel;
use OpenEMR\Core\ModulesClassLoader;
use OpenEMR\Menu\PatientMenuRole;
use OpenEMR\Modules\Escrow\Controller\EscrowReportController;
use OpenEMR\Modules\Escrow\Exception\EscrowModuleException;
use OpenEMR\OeUI\OemrUI;

$classLoader = new ModulesClassLoader($GLOBALS['fileroot']);
$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\Escrow\\',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src'
);

if (!AclMain::aclCheckCore('acct', 'rep') && !AclMain::aclCheckCore('patients', 'demo')) {
    echo xlt('Not authorized');
    exit;
}

$patientId = (int) ($_SESSION['pid'] ?? 0);
$templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates';
$kernel = $GLOBALS['kernel'] ?? new Kernel();
$twig = (new TwigContainer($templatePath, $kernel))->getTwig();

$arrOeUiSettings = [
    'heading_title' => xl('Escrow'),
    'include_patient_name' => true,
    'expandable' => false,
    'expandable_files' => '',
    'action' => '',
    'action_title' => '',
    'action_href' => '',
    'show_help_icon' => false,
    'help_file_name' => '',
];
$oemrUi = new OemrUI($arrOeUiSettings);

$errorMessage = '';
$reportData = [
    'rows' => [],
    'refund' => false,
    'modulePublicPath' => '/interface/modules/custom_modules/oe-module-escrow/public/',
];

try {
    if ($patientId > 0) {
        $controller = new EscrowReportController();
        $reportData = $controller->buildReportData($patientId);
    }
} catch (EscrowModuleException $e) {
    $errorMessage = $e->getMessage();
}

$menuPatient = new PatientMenuRole();
ob_start();
require_once $GLOBALS['fileroot'] . '/interface/patient_file/summary/dashboard_header.php';
$dashboardHeader = ob_get_clean();

ob_start();
$menuPatient->displayHorizNavBarMenu();
$patientMenu = ob_get_clean();

echo $twig->render('escrow/index.html.twig', [
    'title' => xl('Escrow Report'),
    'setupHeader' => Header::setupHeader([], false),
    'oemrContainerClass' => $oemrUi->oeContainer(),
    'dashboardHeader' => $dashboardHeader,
    'patientMenu' => $patientMenu,
    'csrfToken' => CsrfUtils::collectCsrfToken(),
    'webroot' => $GLOBALS['webroot'],
    'patientId' => $patientId,
    'errorMessage' => $errorMessage,
    'rows' => $reportData['rows'],
    'refund' => $reportData['refund'],
    'modulePublicPath' => $reportData['modulePublicPath'],
]);
