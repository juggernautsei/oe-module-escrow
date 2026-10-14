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
        $this->eventDispatcher->addListener(PatientMenuEvent::MENU_UPDATE, [$this, 'addPatientMenuItem']);
    }

    public function addMainMenuItem(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'mod';
        $menuItem->menu_id = 'escrow0';
        $menuItem->label = xlt('Escrow');
        $menuItem->url = $this->getPublicPath() . 'index.php';
        $menuItem->children = [];
        $menuItem->acl_req = ['acct', 'rep'];
        $menuItem->global_req = [];

        foreach ($menu as $item) {
            if (($item->menu_id ?? '') === 'repimg') {
                $item->children[] = $menuItem;
                break;
            }
        }

        $event->setMenu($menu);

        return $event;
    }

    public function addPatientMenuItem(PatientMenuEvent $event): PatientMenuEvent
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->label = xlt('Escrow');
        $menuItem->url = $GLOBALS['webroot'] . $this->getPublicPath() . 'index.php';
        $menuItem->menu_id = 'escrow';
        $menuItem->target = 'esc';
        $menuItem->restriction = [];
        $menuItem->children = [];

        $menu[] = $menuItem;
        $event->setMenu($menu);

        return $event;
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
