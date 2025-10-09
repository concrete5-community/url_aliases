<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Http\ServerInterface;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\RouterInterface;
use Concrete\Core\User\User;
use Doctrine\ORM\EntityManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'url_aliases';

    protected $pkgVersion = '1.1.0';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.5.4';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$phpVersionRequired
     */
    protected $phpVersionRequired = '7.3';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName(): string
    {
        return t('URL Aliases');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription(): string
    {
        return t('Create alias URLs for pages and files.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        $result = parent::install();
        $this->installContentFile('config/install.xml');

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade(): void
    {
        parent::upgrade();
        $this->installContentFile('config/install.xml');
    }

    public function on_start(): void
    {
        $this->app->bind(Entity\UrlAliasRepository::class, static function (Application $app): Entity\UrlAliasRepository {
            return $app->make(EntityManagerInterface::class)->getRepository(Entity\UrlAlias::class);
        });
        $this->app->bind(Entity\NotFoundLogEntryRepository::class, static function (Application $app): Entity\NotFoundLogEntryRepository {
            return $app->make(EntityManagerInterface::class)->getRepository(Entity\NotFoundLogEntry::class);
        });
        if ($this->app->isRunThroughCommandLineInterface()) {
            return;
        }
        $priority = (int) $this->app->make(Repository::class)->get('url_aliases::options.middleware.priority');
        if ($priority < 1) {
            $priority = PHP_INT_MAX;
        }
        if ($this->app->resolved(ServerInterface::class)) {
            $this->app->make(ServerInterface::class)->addMiddleware(new Middleware(), $priority);
        } else {
            $this->app->resolving(ServerInterface::class, static function (ServerInterface $server) use ($priority): void {
                $server->addMiddleware(new Middleware(), $priority);
            });
        }
        if ($this->app->make(User::class)->isRegistered()) {
            $this->registerRoutes();
        }
    }

    private function registerRoutes(): void
    {
        $router = $this->app->make(RouterInterface::class);
        $router->get('/dashboard/system/url-aliases/aliases/edit-url-alias', Controller\Dialog\EditUrlAlias::class . '::view');
        $router->get('/dashboard/system/url-aliases/aliases/edit-localized-target', Controller\Dialog\EditLocalizedTarget::class . '::view');
    }
}
