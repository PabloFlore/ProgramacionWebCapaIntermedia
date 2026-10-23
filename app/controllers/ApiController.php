<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Curso;
use App\Models\Usuario;

class ApiController extends Controller
{
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function cursos(): void
    {
        $categoriaId = $_GET['categoria_id'] ?? null;

        if ($categoriaId === null || $categoriaId === '' || !ctype_digit((string) $categoriaId)) {
            $this->jsonResponse(['error' => 'categoria_id es obligatorio'], 400);
        }

        $categoriaId = (int) $categoriaId;

        $page = isset($_GET['page']) && ctype_digit((string) $_GET['page']) && (int) $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) && ctype_digit((string) $_GET['limit']) && (int) $_GET['limit'] > 0 ? (int) $_GET['limit'] : 10;
        if ($limit > 100) {
            $limit = 100;
        }
        $offset = ($page - 1) * $limit;

        $cursoModel = new Curso();
        $cursos = $cursoModel->listarActivosPorCategoria($categoriaId, $limit, $offset);

        if (empty($cursos)) {
            $this->jsonResponse(['error' => 'No se encontraron cursos para esta categoría'], 404);
        }

        $this->jsonResponse([
            'data' => $cursos,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'categoria_id' => $categoriaId,
            ],
        ], 200);
    }

    public function crearCurso(): void
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            $this->jsonResponse(['error' => 'JSON inválido'], 400);
        }

        $required = ['titulo', 'categoria_id', 'instructor_id'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $this->jsonResponse(['error' => 'Faltan campos obligatorios'], 400);
            }
        }

        $instructorId = (int) $payload['instructor_id'];
        if ($instructorId <= 0) {
            $this->jsonResponse(['error' => 'instructor_id inválido'], 400);
        }

        $usuarioModel = new Usuario();
        $instructor = $usuarioModel->buscarPorId($instructorId);

        if (!$instructor || ($instructor['rol'] !== 'instructor' && $instructor['rol'] !== 'admin')) {
            $this->jsonResponse(['error' => 'No autorizado'], 401);
        }

        if (isset($instructor['bloqueado']) && (int) $instructor['bloqueado'] === 1) {
            $this->jsonResponse(['error' => 'No autorizado'], 401);
        }

        $cursoModel = new Curso();
        $cursoId = $cursoModel->crear([
            'titulo' => trim((string) $payload['titulo']),
            'descripcion' => isset($payload['descripcion']) ? trim((string) $payload['descripcion']) : null,
            'imagen' => isset($payload['imagen']) ? trim((string) $payload['imagen']) : null,
            'precio' => isset($payload['precio']) ? $payload['precio'] : 0.00,
            'tipo_precio' => isset($payload['tipo_precio']) ? $payload['tipo_precio'] : 'curso',
            'instructor_id' => $instructorId,
            'categoria_id' => (int) $payload['categoria_id'],
            'estatus' => isset($payload['estatus']) ? $payload['estatus'] : 'activo',
        ]);

        $this->jsonResponse([
            'mensaje' => 'Curso registrado',
            'curso_id' => $cursoId,
        ], 201);
    }
}
