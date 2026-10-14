<?php

/**
 * Path helpers for symlink-safe module installs.
 *
 * When oe-module-escrow is symlinked from outside the OpenEMR tree,
 * dirname(__DIR__, N) from the realpath no longer reaches interface/globals.php.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Escrow;

require_once __DIR__ . '/Exception/EscrowModuleException.php';

final class ModulePath
{
    /**
     * Absolute path to this module root (directory containing openemr.bootstrap.php).
     */
    public static function moduleRoot(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Absolute path to OpenEMR interface/globals.php.
     */
    public static function globalsPath(): string
    {
        $openemrRoot = self::openemrRoot();
        $globals = $openemrRoot . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'globals.php';
        if (!is_file($globals)) {
            throw new Exception\EscrowModuleException('Unable to locate OpenEMR interface/globals.php from module path.');
        }

        return $globals;
    }

    /**
     * Discover the OpenEMR installation root.
     */
    public static function openemrRoot(): string
    {
        if (!empty($GLOBALS['fileroot']) && is_dir((string) $GLOBALS['fileroot'])) {
            return rtrim((string) $GLOBALS['fileroot'], '/\\');
        }

        $starts = [];
        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            $starts[] = dirname((string) $_SERVER['SCRIPT_FILENAME']);
        }
        // Symlink path under custom_modules when SCRIPT_FILENAME is unresolved.
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            if ($docRoot !== '') {
                $starts[] = dirname(rtrim((string) $docRoot, '/\\') . (string) $_SERVER['SCRIPT_NAME']);
            }
        }
        $starts[] = self::moduleRoot();
        $starts[] = __DIR__;

        foreach (array_unique($starts) as $start) {
            // Prefer the logical path (keeps custom_modules symlink location).
            // Also try realpath for non-symlink installs.
            $candidates = [$start];
            $resolved = realpath($start);
            if ($resolved && $resolved !== $start) {
                $candidates[] = $resolved;
            }

            foreach ($candidates as $dir) {
                for ($i = 0; $i < 12; $i++) {
                    if (is_file($dir . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'globals.php')) {
                        return $dir;
                    }
                    if (
                        basename($dir) === 'interface'
                        && is_file($dir . DIRECTORY_SEPARATOR . 'globals.php')
                    ) {
                        return dirname($dir);
                    }
                    $parent = dirname($dir);
                    if ($parent === $dir) {
                        break;
                    }
                    $dir = $parent;
                }
            }
        }

        // Physical install fallback:
        // public/ -> module -> custom_modules -> modules -> interface -> openemr root
        $fallback = dirname(self::moduleRoot(), 4);
        if (is_file($fallback . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'globals.php')) {
            return $fallback;
        }

        throw new Exception\EscrowModuleException('Unable to resolve OpenEMR root for escrow module.');
    }
}
