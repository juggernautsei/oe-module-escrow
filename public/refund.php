<?php

/**
 * Escrow refund dialog entry point.
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
use OpenEMR\Modules\Escrow\Exception\EscrowModuleException;
use OpenEMR\Modules\Escrow\Service\EscrowService;

$classLoader = new ModulesClassLoader($GLOBALS['fileroot']);
$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\Escrow\\',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src'
);

if (!AclMain::aclCheckCore('acct', 'bill') && !AclMain::aclCheckCore('acct', 'rep')) {
    echo xlt('Not authorized');
    exit;
}

$patientId = (int) ($_SESSION['pid'] ?? 0);
$userId = (int) ($_SESSION['authUserID'] ?? 0);
$templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates';
$kernel = $GLOBALS['kernel'] ?? new Kernel();
$twig = (new TwigContainer($templatePath, $kernel))->getTwig();

$statusMessage = '';
$statusError = '';

if (!empty($_POST['checkno']) && !empty($_POST['amount']) && !empty($_POST['checkdate'])) {
    if (!CsrfUtils::verifyCsrfToken($_POST['csrf_token_form'] ?? '')) {
        CsrfUtils::csrfNotVerified();
    }

    try {
        $service = new EscrowService();
        $service->reference = trim((string) $_POST['checkno']);
        $service->payTotal = trim((string) $_POST['amount']);
        $service->checkDate = trim((string) $_POST['checkdate']);
        $service->enterRefundedAmount($patientId, $userId);
        $statusMessage = xl('Refund Successful');
    } catch (EscrowModuleException $e) {
        $statusError = $e->getMessage();
    }
}

echo $twig->render('escrow/refund.html.twig', [
    'title' => xl('Escrow Refund'),
    'setupHeader' => Header::setupHeader(['common', 'datetime-picker'], false),
    'csrfToken' => CsrfUtils::collectCsrfToken(),
    'statusMessage' => $statusMessage,
    'statusError' => $statusError,
    'datetimepickerScript' => $GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php',
]);
