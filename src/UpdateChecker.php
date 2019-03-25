<?php

namespace DigipolisGent\UpdateChecker;

use Composer\Composer;
use Composer\DependencyResolver\Pool;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\ComposerRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Semver\Semver;

class UpdateChecker
{
    const UPDATETYPE_MAJOR = 'major';
    const UPDATETYPE_MINOR = 'minor';
    /**
     * Composer instance.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * Package pool.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * @var array
     */
    protected $packages;


    public function __construct(Composer $composer, Pool $pool)
    {
        $this->composer = $composer;
        $this->pool = $pool;
        $this->packages = [];

        $this->init();
    }

    protected function init()
    {
        $rootPkg = $this->composer->getPackage();
        $installedRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        if (!$installedRepo->getPackages() && ($rootPkg->getRequires() || $rootPkg->getDevRequires())) {
            throw new \Exception('No dependencies installed. Try running composer install or update.');
        }

        $repos = $this->composer->getRepositoryManager()->getLocalRepository();
        if ($repos instanceof CompositeRepository) {
            $repos = $repos->getRepositories();
        }
        elseif (!is_array($repos)) {
            $repos = array($repos);
        }

        foreach ($repos as $repo) {
            if ($repo instanceof ComposerRepository && $repo->hasProviders()) {
                foreach ($repo->getProviderNames() as $name) {
                    $this->packages[$name] = $name;
                }
                continue;
            }
            foreach ($repo->getPackages() as $package) {
                if (!isset($this->packages[$package->getName()]) || !is_object($this->packages[$package->getName()]) || version_compare($this->packages[$package->getName()]->getVersion(), $package->getVersion(), '<')
                ) {
                    $this->packages[$package->getName()] = $package;
                }
            }
        }
        ksort($this->packages);
    }

    /**
     * @return \DigipolisGent\UpdateChecker\PackageReport[]
     */
    public function getReport()
    {
        $viewData = array();
        foreach ($this->packages as $package) {
            $major = $this->findLatestPackage($package, static::UPDATETYPE_MAJOR);
            $minor = $this->findLatestPackage($package, static::UPDATETYPE_MINOR);
            if ($major === false && $minor === false) {
                continue;
            }
            $viewData[] = new PackageReport($package, $minor, $major);
        }
        return $viewData;
    }

    protected function findLatestPackage(PackageInterface $package, $updateType) {
        $name = $package->getName();
        $versionSelector = new VersionSelector($this->pool);
        $stability = $this->composer->getPackage()->getMinimumStability();
        $flags = $this->composer->getPackage()->getStabilityFlags();
        if (isset($flags[$name])) {
            $stability = array_search($flags[$name], BasePackage::$stabilities, true);
        }

        $bestStability = $stability;
        if ($this->composer->getPackage()->getPreferStable()) {
            $bestStability = $package->getStability();
        }

        $targetVersion = null;
        if (0 === strpos($package->getVersion(), 'dev-')) {
            $targetVersion = $package->getVersion();
        }

        if ($targetVersion === null && $updateType === static::UPDATETYPE_MINOR) {
            $targetVersion = '^' . $package->getVersion();
        }

        $platformOverrides = $this->composer->getConfig()->get('platform') ?: array();
        $platformRepo = new PlatformRepository(array(), $platformOverrides);
        $candidate = $versionSelector->findBestCandidate($name, $targetVersion, $platformRepo->findPackage('php', '*')->getVersion(), $bestStability);
        if ($updateType === static::UPDATETYPE_MAJOR) {
            return $this->isMajorUpdate($candidate, $package) ? $candidate : false;
        }
        return $this->isMinorUpdate($candidate, $package) ? $candidate : false;
    }

    protected function isMajorUpdate(PackageInterface $latestPackage, PackageInterface $package) {
        if ($latestPackage->getFullPrettyVersion() === $package->getFullPrettyVersion()) {
            return false;
        }

        $constraint = $package->getVersion();
        if (0 !== strpos($constraint, 'dev-')) {
            $constraint = '^' . $constraint;
        }
        if ($latestPackage->getVersion() && Semver::satisfies($latestPackage->getVersion(), $constraint)) {
            return false;
        }

        return true;
    }

    protected function isMinorUpdate(PackageInterface $latestPackage, PackageInterface $package) {
        return $latestPackage->getFullPrettyVersion() !== $package->getFullPrettyVersion()
            && !$this->isMajorUpdate($latestPackage, $package);
    }
}
