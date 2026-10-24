<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Models\Categoria;
use App\Models\Curso;
use App\Models\Usuario;

class ApiController extends JsonController
{
    private const ROLES_INSTRUCTOR = ['instructor', 'admin'];
    private const ESTATUS = ['activo', 'inactivo'];
    private const TIPOS_PRECIO = ['curso', 'nivel'];
    private const MAX_TITULO = 150;
    private const MAX_IMAGEN = 255;
    private const MAX_PRECIO = 99999999.99;
    private const MAX_LIMIT = 100;

    private function isPositiveIntString(mixed $value): bool
    {
        return (is_int($value) && $value > 0)
            || (is_string($value) && ctype_digit($value) && (int) $value > 0);
    }

    public function cursos(): void
    {
        $categoriaId = $_GET['categoria_id'] ?? null;

        if (!$this->isPositiveIntString($categoriaId)) {
            $this->error(400, 'categoria_id es obligatorio y debe ser un entero positivo');
            return;
        }

        $categoriaId = (int) $categoriaId;

        $page = $this->isPositiveIntString($_GET['page'] ?? null) ? (int) $_GET['page'] : 1;
        $limit = $this->isPositiveIntString($_GET['limit'] ?? null) ? (int) $_GET['limit'] : 10;
        $limit = min($limit, self::MAX_LIMIT);
        $offset = ($page - 1) * $limit;

        $cursoModel = new Curso();
        $cursos = $cursoModel->listarActivosPorCategoria($categoriaId, $limit, $offset);

        if ($cursos === []) {
            $this->error(404, 'No se encontraron cursos para esta categoría');
            return;
        }

        $total = $cursoModel->contarActivosPorCategoria($categoriaId);

        $this->respond([
            'data' => $cursos,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'categoria_id' => $categoriaId,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ], 200);
    }

    public function crearCurso(): void
    {
        $instructorId = Session::usuarioId();

        if ($instructorId === null) {
            $this->error(401, 'Se requiere iniciar sesión');
            return;
        }

        $instructor = (new Usuario())->buscarPorId($instructorId);

        if ($instructor === null
            || (int) $instructor['bloqueado'] === 1
            || !in_array($instructor['rol'], self::ROLES_INSTRUCTOR, true)
        ) {
            $this->error(401, 'No tienes permisos para registrar cursos');
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $errores = $this->validarCurso($payload);
        if ($errores !== []) {
            $this->respond(['error' => $errores[0], 'detalles' => $errores], 400);
            return;
        }

        $cursoId = (new Curso())->crear([
            'titulo' => trim($payload['titulo']),
            'descripcion' => isset($payload['descripcion']) ? trim($payload['descripcion']) : null,
            'imagen' => isset($payload['imagen']) ? trim($payload['imagen']) : null,
            'precio' => isset($payload['precio']) ? (float) $payload['precio'] : 0.00,
            'tipo_precio' => $payload['tipo_precio'] ?? 'curso',
            'instructor_id' => $instructorId,
            'categoria_id' => (int) $payload['categoria_id'],
            'estatus' => $payload['estatus'] ?? 'activo',
        ]);

        $this->respond([
            'mensaje' => 'Curso registrado',
            'curso_id' => $cursoId,
        ], 201);
    }

    private function validarCurso(array $payload): array
    {
        $errores = [];

        $faltantes = [];
        if (!isset($payload['titulo']) || !is_string($payload['titulo']) || trim($payload['titulo']) === '') {
            $faltantes[] = 'titulo';
        }
        if (!isset($payload['categoria_id']) || $payload['categoria_id'] === '') {
            $faltantes[] = 'categoria_id';
        }
        if ($faltantes !== []) {
            return ['Faltan campos obligatorios: ' . implode(', ', $faltantes)];
        }

        if (mb_strlen(trim($payload['titulo'])) > self::MAX_TITULO) {
            $errores[] = 'titulo no debe exceder ' . self::MAX_TITULO . ' caracteres';
        }

        if (isset($payload['descripcion']) && !is_string($payload['descripcion'])) {
            $errores[] = 'descripcion debe ser texto';
        }

        if (isset($payload['imagen'])
            && (!is_string($payload['imagen']) || mb_strlen($payload['imagen']) > self::MAX_IMAGEN)
        ) {
            $errores[] = 'imagen debe ser texto de máximo ' . self::MAX_IMAGEN . ' caracteres';
        }

        if (!$this->isPositiveIntString($payload['categoria_id'])) {
            $errores[] = 'categoria_id debe ser un entero positivo';
        } elseif (!(new Categoria())->existe((int) $payload['categoria_id'])) {
            $errores[] = 'categoria_id no existe';
        }

        if (isset($payload['precio'])) {
            $precio = $payload['precio'];
            if (!is_numeric($precio) || (float) $precio < 0 || (float) $precio > self::MAX_PRECIO) {
                $errores[] = 'precio debe ser un número entre 0 y ' . self::MAX_PRECIO;
            }
        }

        if (isset($payload['tipo_precio']) && !in_array($payload['tipo_precio'], self::TIPOS_PRECIO, true)) {
            $errores[] = 'tipo_precio debe ser: ' . implode(' o ', self::TIPOS_PRECIO);
        }

        if (isset($payload['estatus']) && !in_array($payload['estatus'], self::ESTATUS, true)) {
            $errores[] = 'estatus debe ser: ' . implode(' o ', self::ESTATUS);
        }

        return $errores;
    }
}
