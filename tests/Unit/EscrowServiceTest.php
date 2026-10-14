<?php

declare(strict_types=1);

namespace OpenEMR\Modules\Escrow\Tests\Unit;

use OpenEMR\Modules\Escrow\Service\EscrowService;
use PHPUnit\Framework\TestCase;

class EscrowServiceTest extends TestCase
{
    /**
     * Test service defaults are initialized.
     */
    public function testServiceDefaults(): void
    {
        $service = new EscrowService();

        $this->assertSame(0, $service->arSessionId);
        $this->assertSame(0, $service->encounter);
        $this->assertSame('', $service->checkDate);
        $this->assertSame('', $service->payTotal);
        $this->assertSame('', $service->reference);
    }
}
