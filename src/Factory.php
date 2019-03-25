<?php

namespace DigipolisGent\UpdateChecker;

use Composer\DependencyResolver\Pool;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Repository\CompositeRepository;

class Factory
{
    public function create($path, IOInterface $io = null) {
        if (!$io) {
             $io = new NullIO();
        }
        // Composer is weird.
        $oldDir = getcwd();
        chdir($path);
        $composer = ComposerFactory::create($io);
        chdir($oldDir);
        $pool = new Pool($composer->getPackage()->getMinimumStability(), $composer->getPackage()->getStabilityFlags());
        $pool->addRepository(new CompositeRepository($composer->getRepositoryManager()->getRepositories()));
        return new UpdateChecker($composer, $pool);
    }
}
