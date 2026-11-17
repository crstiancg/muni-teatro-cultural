<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // SpaController necesita el index.html del build de Quasar (spa:publicar).
        // Los tests usan una plantilla mínima que imita el build minificado, así
        // no dependen de haber compilado el front.
        config(['seo.spa_index' => __DIR__ . '/fixtures/spa-index.html']);
    }
}
