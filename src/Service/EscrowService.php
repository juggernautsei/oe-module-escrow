<?php

/**
 * Escrow pre-payment and refund data access.
 *
 * Port of interface/reports/escrow/Escrow.php for v8 module packaging.
 * Uses core ar_session / ar_activity only (no module tables).
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Escrow\Service;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\Escrow\Exception\EscrowModuleException;

class EscrowService
{
    public int $arSessionId = 0;
    public int $encounter = 0;
    public string $checkDate = '';
    public string $payTotal = '';
    public string $reference = '';

    /**
     * @return list<array<string, mixed>>
     */
    public function retrieveAllEscrowPayments(int $patientId): array
    {
        $sql = <<<SQL
            SELECT `session_id`, `check_date`, `pay_total`, `payment_method`
            FROM `ar_session`
            WHERE `patient_id` = ?
              AND `adjustment_code` = ?
            SQL;

        return QueryUtils::fetchRecords($sql, [$patientId, 'pre_payment']) ?: [];
    }

    /**
     * @return array<string, mixed>|false
     */
    public function getRefund(int $patientId): array|false
    {
        $sql = <<<SQL
            SELECT `session_id`, `reference`, `check_date`, `pay_total`
            FROM `ar_session`
            WHERE `patient_id` = ?
              AND `adjustment_code` = 'refund_balance'
            SQL;

        $row = QueryUtils::querySingleRow($sql, [$patientId]);

        return $row ?: false;
    }

    public function enterRefundedAmount(int $patientId, int $userId): string
    {
        $sql = <<<SQL
            INSERT INTO `ar_session` (
                `session_id`,
                `payer_id`,
                `user_id`,
                `closed`,
                `reference`,
                `check_date`,
                `deposit_date`,
                `pay_total`,
                `created_time`,
                `modified_time`,
                `global_amount`,
                `payment_type`,
                `description`,
                `adjustment_code`,
                `post_to_date`,
                `patient_id`,
                `payment_method`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, NOW(), ?, ?
            )
            SQL;

        try {
            QueryUtils::sqlStatementThrowException($sql, [
                '',
                0,
                $userId,
                0,
                $this->reference,
                $this->checkDate,
                $this->checkDate,
                $this->payTotal,
                '0.00',
                'clinic',
                'Balance Refund',
                'refund_balance',
                $patientId,
                'check_payment',
            ]);
        } catch (\Throwable $e) {
            throw new EscrowModuleException(
                'Failed to record escrow refund: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return 'success';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getEncounterPayments(): array
    {
        $sql = <<<SQL
            SELECT ar.encounter, fe.date, SUM(ar.pay_amount) AS pay_amount
            FROM `ar_activity` AS ar
            LEFT JOIN `form_encounter` fe ON fe.encounter = ar.encounter
            WHERE ar.encounter = ?
              AND ar.session_id = ?
              AND ar.account_code = 'PP'
              AND ar.deleted IS NULL
            SQL;

        return QueryUtils::fetchRecords($sql, [$this->encounter, $this->arSessionId]) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getEncounterEntries(): array
    {
        $sql = <<<SQL
            SELECT `encounter`
            FROM `ar_activity`
            WHERE `session_id` = ?
              AND `account_code` = 'PP'
              AND `deleted` IS NULL
            GROUP BY `encounter`
            SQL;

        return QueryUtils::fetchRecords($sql, [$this->arSessionId]) ?: [];
    }
}
