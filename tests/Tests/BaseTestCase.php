<?php
namespace EngineWorks\DBAL\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

class BaseTestCase extends TestCase
{
    /**
     * Mark the test as skipped if the PHPUnit Serie is lower than the parameter.
     * If no \PHPUnit\Runner\Version class exists then it will assume is running phpunit 5.7
     *
     * @param string $minimalVersion
     */
    public function checkPhpUnitVersion($minimalVersion)
    {
        $phpUnitVersion = '5.7';
        if (class_exists(Version::class)) {
            $phpUnitVersion = Version::series();
        }
        if (version_compare($phpUnitVersion, $minimalVersion, '<')) {
            $this->markTestSkipped(sprintf('This test only runs on phpunit %s or higher', $minimalVersion));
        }
    }
}
