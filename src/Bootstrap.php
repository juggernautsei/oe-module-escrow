<?php

/**
 * Escrow module event orchestration.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Escrow;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Menu\PatientMenuEvent;
use OpenEMR\Modules\Escrow\Menu\EscrowMenuItems;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    public const MODULE_INSTALLATION_PATH = '/interface/modules/custom_modules/';
    public const MODULE_NAME = 'oe-module-escrow';

    private EventDispatcherInterface $eventDispatcher;
    private string $moduleDirectoryName;
    private ?Environment $twig;
    private SystemLogger $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        if ($kernel === null) {
            $kernel = new Kernel();
        }

        $this->moduleDirectoryName = basename(dirname(__DIR__));
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = new SystemLogger();

        $twigContainer = new TwigContainer($this->getTemplatePath(), $kernel);
        $this->twig = $twigContainer->getTwig();
    }

    public function subscribeToEvents(): void
    {
        $this->registerMenuItems();
        $this->registerTemplateEvents();
    }

    public function registerMenuItems(): void
    {
        $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'addMainMenuItem']);
        // Low priority so we add Escrow and normalize after other modules contribute items.
        $this->eventDispatcher->addListener(PatientMenuEvent::MENU_UPDATE, [$this, 'addPatientMenuItem'], -1000);
    }

    public function addMainMenuItem(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();
        $menuItem = $this->buildReportsMenuItem();

        foreach ($menu as $item) {
            if (($item->menu_id ?? '') !== 'repimg') {
                continue;
            }

            if (!isset($item->children) || !is_array($item->children)) {
                $item->children = [];
            }

            if (!EscrowMenuItems::containsId($item->children, 'escrow0')) {
                $item->children[] = $menuItem;
            }
            break;
        }

        $event->setMenu($menu);

        return $event;
    }

    public function addPatientMenuItem(PatientMenuEvent $event): PatientMenuEvent
    {
        $menu = $event->getMenu();
        if (!EscrowMenuItems::containsId($menu, 'escrow')) {
            $menu[] = $this->buildPatientMenuItem();
        }

        // Normalize sibling module/core entries so horizontal nav rendering is safe.
        $event->setMenu(EscrowMenuItems::normalizePatientMenu($menu));

        return $event;
    }

    /**
     * Reports main-menu entry.
     */
    public function buildReportsMenuItem(): \stdClass
    {
        return EscrowMenuItems::reportsItem($this->getPublicPath());
    }

    /**
     * Patient horizontal nav entry with PatientMenuRole-required fields.
     */
    public function buildPatientMenuItem(): \stdClass
    {
        return EscrowMenuItems::patientItem(
            (string) ($GLOBALS['webroot'] ?? ''),
            $this->getPublicPath()
        );
    }

    public function registerTemplateEvents(): void
    {
        $this->eventDispatcher->addListener(
            TwigEnvironmentEvent::EVENT_CREATED,
            [$this, 'addTemplateOverrideLoader']
        );
    }

    public function addTemplateOverrideLoader(TwigEnvironmentEvent $event): void
    {
        try {
            $twig = $event->getTwigEnvironment();
            if ($twig === $this->twig) {
                return;
            }

            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath($this->getTemplatePath());
            }
        } catch (LoaderError $error) {
            $this->logger->errorLogCaller('Failed to create escrow template loader', [
                'innerMessage' => $error->getMessage(),
                'trace' => $error->getTraceAsString(),
            ]);
        }
    }

    public function getTwig(): ?Environment
    {
        return $this->twig;
    }

    public function getTemplatePath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
    }

    public function getPublicPath(): string
    {
        return self::MODULE_INSTALLATION_PATH . $this->moduleDirectoryName . '/public/';
    }
}
