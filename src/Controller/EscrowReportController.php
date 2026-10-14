<?php

/**
 * Escrow report page controller.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Escrow\Controller;

use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Modules\Escrow\Bootstrap;
use OpenEMR\Modules\Escrow\Exception\EscrowModuleException;
use OpenEMR\Modules\Escrow\Service\EscrowService;
use Twig\Environment;

class EscrowReportController
{
    private EscrowService $escrowService;
    private Environment $twig;

    public function __construct(?EscrowService $escrowService = null, ?Environment $twig = null)
    {
        $this->escrowService = $escrowService ?? new EscrowService();

        if ($twig !== null) {
            $this->twig = $twig;
            return;
        }

        $templatePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates';
        $kernel = $GLOBALS['kernel'] ?? new Kernel();
        $this->twig = (new TwigContainer($templatePath, $kernel))->getTwig();
    }

    /**
     * Build view model rows for the escrow report.
     *
     * @return array{
     *   payments: list<array<string, mixed>>,
     *   refund: array<string, mixed>|false,
     *   rows: list<array<string, mixed>>,
     *   modulePublicPath: string
     * }
     */
    public function buildReportData(int $patientId): array
    {
        if ($patientId <= 0) {
            throw new EscrowModuleException('A valid patient id is required for the escrow report.');
        }

        $payments = $this->escrowService->retrieveAllEscrowPayments($patientId);
        $refund = $this->escrowService->getRefund($patientId);
        $rows = [];

        foreach ($payments as $amount) {
            $this->escrowService->arSessionId = (int) ($amount['session_id'] ?? 0);
            $encounters = $this->escrowService->getEncounterEntries();
            $sumOfPayments = 0.0;
            $allocationRows = [];

            foreach ($encounters as $encounter) {
                $this->escrowService->encounter = (int) ($encounter['encounter'] ?? 0);
                $paymentTotals = $this->escrowService->getEncounterPayments();
                $payAmount = (float) ($paymentTotals[0]['pay_amount'] ?? 0);
                $sumOfPayments += $payAmount;
                $allocationRows[] = [
                    'date' => isset($paymentTotals[0]['date'])
                        ? substr((string) $paymentTotals[0]['date'], 0, 10)
                        : '',
                    'encounter' => $paymentTotals[0]['encounter'] ?? null,
                    'pay_amount' => $payAmount,
                ];
            }

            $lessRefund = empty($refund['pay_total']) ? 0.0 : (float) $refund['pay_total'];
            $remaining = (float) ($amount['pay_total'] ?? 0) - $sumOfPayments;

            $rows[] = [
                'session' => $amount,
                'allocations' => $allocationRows,
                'sum_of_payments' => $sumOfPayments,
                'remaining_balance' => $lessRefund + $remaining,
                'refund' => $refund,
            ];
        }

        return [
            'payments' => $payments,
            'refund' => $refund,
            'rows' => $rows,
            'modulePublicPath' => Bootstrap::MODULE_INSTALLATION_PATH . Bootstrap::MODULE_NAME . '/public/',
        ];
    }

    public function renderReport(int $patientId): string
    {
        $data = $this->buildReportData($patientId);

        return $this->twig->render('escrow/index.html.twig', $data);
    }
}
