<?php

/**
 * Menu entry builders for Escrow module.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Escrow\Menu;

use OpenEMR\Modules\Escrow\Bootstrap;
use stdClass;

final class EscrowMenuItems
{
    /**
     * Reports main-menu entry under Reports.
     */
    public static function reportsItem(string $publicPath, string $label = 'Escrow'): stdClass
    {
        $menuItem = new stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'mod';
        $menuItem->menu_id = 'escrow0';
        $menuItem->label = function_exists('xlt') ? xlt($label) : $label;
        $menuItem->url = $publicPath . 'index.php';
        $menuItem->children = [];
        $menuItem->acl_req = ['acct', 'rep'];
        $menuItem->global_req = [];

        return $menuItem;
    }

    /**
     * Patient horizontal nav entry.
     *
     * PatientMenuRole::displayHorizNavBarMenu() requires pid and on_click
     * on top-level items (matches standard.json patient menu entries).
     */
    public static function patientItem(string $webroot, string $publicPath, string $label = 'Escrow'): stdClass
    {
        $menuItem = new stdClass();
        $menuItem->label = function_exists('xlt') ? xlt($label) : $label;
        $menuItem->menu_id = 'escrow';
        $menuItem->target = 'main';
        $menuItem->on_click = 'top.restoreSession()';
        $menuItem->url = rtrim($webroot, '/') . $publicPath . 'index.php';
        $menuItem->pid = 'false';
        $menuItem->children = [];
        $menuItem->requirement = 0;
        $menuItem->restriction = [];

        return $menuItem;
    }

    /**
     * Default public path for this module folder name.
     */
    public static function defaultPublicPath(?string $moduleDirectoryName = null): string
    {
        $dir = $moduleDirectoryName ?: Bootstrap::MODULE_NAME;

        return Bootstrap::MODULE_INSTALLATION_PATH . $dir . '/public/';
    }

    /**
     * @param array<int, object> $items
     */
    public static function containsId(array $items, string $menuId): bool
    {
        foreach ($items as $item) {
            if (($item->menu_id ?? '') === $menuId) {
                return true;
            }

            if (!empty($item->children) && is_array($item->children) && self::containsId($item->children, $menuId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure patient menu objects satisfy PatientMenuRole::displayHorizNavBarMenu().
     *
     * Other modules sometimes omit pid/on_click; missing properties emit warnings
     * when the horizontal nav is rendered on our report page.
     *
     * @param array<int, object> $items
     * @return array<int, object>
     */
    public static function normalizePatientMenu(array $items): array
    {
        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }

            if (!property_exists($item, 'pid') || $item->pid === null || $item->pid === '') {
                $item->pid = 'false';
            }
            if (!property_exists($item, 'on_click') || $item->on_click === null || $item->on_click === '') {
                $item->on_click = 'top.restoreSession()';
            }
            if (!property_exists($item, 'children') || !is_array($item->children)) {
                $item->children = [];
            } elseif ($item->children !== []) {
                $item->children = self::normalizePatientMenu($item->children);
            }
            if (!property_exists($item, 'requirement')) {
                $item->requirement = 0;
            }
            if (!property_exists($item, 'menu_id')) {
                $item->menu_id = $item->label ?? 'menu_item';
            }
            if (!property_exists($item, 'url')) {
                $item->url = '#';
            }
            if (!property_exists($item, 'label')) {
                $item->label = '';
            }
        }

        return $items;
    }
}
