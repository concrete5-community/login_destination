<?php

namespace Concrete\Package\LoginDestination;

use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Package\Package;
use Concrete\Core\User\PostLoginLocation;
use LoginDestination\CustomPostLoginLocation;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * The package controller.
 *
 * Manages the package installation, update and start-up.
 */
class Controller extends Package implements ProviderAggregateInterface
{
    /**
     * The minimum concrete5 version.
     *
     * @var string
     */
    protected $appVersionRequired = '8.2.0';

    /**
     * The unique handle that identifies the package.
     *
     * @var string
     */
    protected $pkgHandle = 'login_destination';

    /**
     * The package version.
     *
     * @var string
     */
    protected $pkgVersion = '0.9.1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Login Destination');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Allow customizing where a user is redirected upon login.');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface::getEntityManagerProvider()
     */
    public function getEntityManagerProvider()
    {
        return new StandardPackageProvider($this->app, $this, [
            'src/Entity' => 'LoginDestination\Entity',
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        $this->registerAutoload();
        parent::install();
        $this->installXml();
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installXml();
    }

    /**
     * Initialize the package.
     */
    public function on_start()
    {
        $this->registerAutoload();
        $this->app->bind(PostLoginLocation::class, CustomPostLoginLocation::class);
    }

    /**
     * Install/update data from install XML file.
     */
    private function installXml()
    {
        parent::installContentFile('config/install.xml');
    }

    private function registerAutoload()
    {
        $autoloader = $this->getPackagePath() . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }
    }
}
