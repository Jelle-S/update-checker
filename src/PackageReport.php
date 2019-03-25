<?php

namespace DigipolisGent\UpdateChecker;

use Composer\Package\PackageInterface;

class PackageReport
{
    /**
     * @var PackageInterface
     */
    protected $package;

    /**
     * @var PackageInterface|bool
     */
    protected $minor;

    /**
     * @var PackageInterface|bool
     */
    protected $major;

    public function __construct(PackageInterface $package, $minor, $major)
    {
        $this->package = $package;
        $this->minor = $minor;
        $this->major = $major;
    }

    /**
     * @return PackageInterface
     */
    public function getPackage() {
      return $this->package;
    }

    /**
     * @return PackageInterface|bool
     */
    public function getMinor() {
      return $this->minor;
    }

    /**
     * @return PackageInterface|bool
     */
    public function getMajor() {
      return $this->major;
    }

    public function getCurrentVersion()
    {
        return $this->package->getFullPrettyVersion();
    }

    public function getMinorUpdateVersion()
    {
        return $this->minor ? $this->minor->getFullPrettyVersion() : false;
    }

    public function getMajorUpdateVersion()
    {
        return $this->major ? $this->major->getFullPrettyVersion() : false;
    }

    public function getWarning() {
        if (($this->major && $this->major->isAbandoned()) || ($this->minor && $this->minor->isAbandoned())) {
            $abandoned = $this->major->isAbandoned() ? $this->major : $this->minor;
            $replacement = is_string($abandoned->getReplacementPackage()) ? 'Use ' . $abandoned->getReplacementPackage() . ' instead' : 'No replacement was suggested';
            return sprintf(
                'Package %s is abandoned, you should avoid using it. %s.',
                $this->package->getPrettyName(),
                $replacement
            );
        }
    }
}
