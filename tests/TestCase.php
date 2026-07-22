<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionProperty;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // O Eloquent cacheia por classe, em estático, as colunas que podem entrar
        // no mass assignment (GuardsAttributes::$guardableColumns). Testes de
        // importação que criam um schema mínimo na mão (Associados/Rm) poluiriam
        // esse cache para as classes de teste seguintes — campos passariam a ser
        // descartados em silêncio mesmo com o schema completo migrado.
        $guardable = new ReflectionProperty(Model::class, 'guardableColumns');
        $guardable->setValue(null, []);

        parent::tearDown();
    }
}
