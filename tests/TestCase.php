<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\SectorSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedMealMasterBasics(): void
    {
        $this->seed([
            SectorSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
