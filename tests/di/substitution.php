<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/autoload.php';

use App\Controllers\KardexController;
use App\Core\Container;
use App\Services\Contracts\KardexServiceInterface;

final class KardexFake implements KardexServiceInterface
{
    public function obtener(int $estudianteId): array
    {
        return [
            'resumen' => [
                'total' => 1,
                'completados' => 1,
                'en_curso' => 0,
                'pagadas' => 1,
            ],
            'inscripciones' => [
                ['inscripcion_id' => 99, 'estudiante_id' => $estudianteId],
            ],
        ];
    }
}

$container = new Container();
$fake = new KardexFake();
$container->singleton(KardexServiceInterface::class, $fake);

$controller = $container->get(KardexController::class);
$property = new ReflectionProperty(KardexController::class, 'kardex');
$property->setAccessible(true);

if ($property->getValue($controller) !== $fake) {
    fwrite(STDERR, "DI substitution failed: controller did not receive the fake service.\n");
    exit(1);
}

$resultado = $fake->obtener(7);

if (($resultado['inscripciones'][0]['estudiante_id'] ?? null) !== 7) {
    fwrite(STDERR, "DI substitution failed: fake service returned unexpected data.\n");
    exit(1);
}

echo "DI substitution OK\n";
