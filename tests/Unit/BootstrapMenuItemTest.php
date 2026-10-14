<?php

declare(strict_types=1);

namespace OpenEMR\Modules\Escrow\Tests\Unit;

use OpenEMR\Modules\Escrow\Menu\EscrowMenuItems;
use PHPUnit\Framework\TestCase;

class BootstrapMenuItemTest extends TestCase
{
    /**
     * Patient menu items must include properties required by PatientMenuRole.
     */
    public function testPatientMenuItemHasRequiredProperties(): void
    {
        $item = EscrowMenuItems::patientItem('/openemr', EscrowMenuItems::defaultPublicPath());

        $this->assertSame('escrow', $item->menu_id);
        $this->assertSame('main', $item->target);
        $this->assertSame('false', $item->pid);
        $this->assertSame('top.restoreSession()', $item->on_click);
        $this->assertSame(0, $item->requirement);
        $this->assertIsArray($item->children);
        $this->assertSame(
            '/openemr/interface/modules/custom_modules/oe-module-escrow/public/index.php',
            $item->url
        );
    }

    /**
     * Reports menu item should expose ACL and stable menu id.
     */
    public function testReportsMenuItemHasAclAndId(): void
    {
        $item = EscrowMenuItems::reportsItem(EscrowMenuItems::defaultPublicPath());

        $this->assertSame('escrow0', $item->menu_id);
        $this->assertSame(['acct', 'rep'], $item->acl_req);
        $this->assertSame([], $item->global_req);
        $this->assertSame(0, $item->requirement);
        $this->assertSame(
            '/interface/modules/custom_modules/oe-module-escrow/public/index.php',
            $item->url
        );
    }

    /**
     * Duplicate detection helper used when menu events re-fire.
     */
    public function testContainsIdFindsNestedMenuId(): void
    {
        $parent = new \stdClass();
        $parent->menu_id = 'repimg';
        $parent->children = [EscrowMenuItems::reportsItem(EscrowMenuItems::defaultPublicPath())];

        $this->assertTrue(EscrowMenuItems::containsId([$parent], 'escrow0'));
        $this->assertFalse(EscrowMenuItems::containsId([$parent], 'missing'));
    }

    /**
     * Incomplete menu entries gain PatientMenuRole-required defaults.
     */
    public function testNormalizePatientMenuFillsMissingProperties(): void
    {
        $bare = new \stdClass();
        $bare->label = 'Broken';
        $bare->url = '/x';

        $normalized = EscrowMenuItems::normalizePatientMenu([$bare]);
        $item = $normalized[0];

        $this->assertSame('false', $item->pid);
        $this->assertSame('top.restoreSession()', $item->on_click);
        $this->assertSame(0, $item->requirement);
        $this->assertSame('Broken', $item->menu_id);
        $this->assertIsArray($item->children);
    }
}
