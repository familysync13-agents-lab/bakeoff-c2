<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against the application (PostgreSQL when DATABASE_URL is
| set, as in the Dockerfile `check` stage; SQLite otherwise).
|
*/

pest()->extend(TestCase::class)->in('Feature');
