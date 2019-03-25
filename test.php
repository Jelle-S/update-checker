<?php

use DigipolisGent\UpdateChecker\Factory;
use SensioLabs\Security\SecurityChecker;

require __DIR__ . '/vendor/autoload.php';

// Add some directories with composer-based projects.
$dirs = [];
foreach ($dirs as $dir) {
  $factory = new Factory();
  $updateChecker = $factory->create($dir);

  $securityChecker = new SecurityChecker();
  $result = $securityChecker->check($dir, 'json');
  $alerts = json_decode((string) $result, true);

  $header = str_pad(basename($dir), 40) . str_pad('CURRENT', 20) . str_pad('MINOR', 20) . str_pad('MAJOR', 20);
  echo $header . PHP_EOL . str_repeat('=', strlen($header)) . PHP_EOL;
  foreach ($updateChecker->getReport() as $packageReport) {
    echo str_pad($packageReport->getPackage()->getName(), 40) . str_pad($packageReport->getCurrentVersion(), 20) . str_pad($packageReport->getMinorUpdateVersion(), 20) . str_pad($packageReport->getMajorUpdateVersion(), 20) . PHP_EOL;
    if (isset($alerts[$packageReport->getPackage()->getName()])) {
      echo '  SECURITY:' . PHP_EOL;
      foreach ($alerts[$packageReport->getPackage()->getName()]['advisories'] as $advisory) {
        echo '  ' . ($advisory['cve'] ? $advisory['cve'] . ': ' : '') . '[' . $advisory['title'] . '](' . $advisory['link'] . ')' . PHP_EOL;
      }
    }
  }
}
