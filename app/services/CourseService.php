<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Categoria;
use App\Models\Contracts\UsuarioRepositoryInterface;
use App\Models\Curso;
use App\Models\Nivel;
use PDOException;

final class CourseService
{
    private const TIPOS_PRECIO = ['curso', 'nivel'];
    private const ESTATUS = ['activo', 'inactivo'];

    public function __construct(
        private readonly Curso $cursos,
        private readonly Categoria $categorias,
        private readonly Nivel $niveles,
        private readonly UsuarioRepositoryInterface $usuarios
    ) {
    }

    public function detalle(int $cursoId): ?array
    {
        $curso = $this->cursos->buscarPorId($cursoId);

        if ($curso === null) {
            return null;
        }

        $curso['niveles'] = $this->niveles->listarPorCurso($cursoId);

        return $curso;
    }

    public function listarGestion(?int $instructorId, int $pagina, int $limite): array
    {
        $pagina = max(1, $pagina);
        $limite = min(50, max(1, $limite));
        $offset = ($pagina - 1) * $limite;
        $total = $this->cursos->contar(null, $instructorId);

        return [
            'data' => $this->cursos->listar(null, $instructorId, $limite, $offset),
            'pagination' => [
                'page' => $pagina,
                'limit' => $limite,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $limite)),
            ],
        ];
    }

    public function crear(array $datos, int $usuarioId, string $rol): array
    {
        $validacion = $this->validarCurso($datos);

        if ($validacion['errores'] !== []) {
            return $this->fallo(400, $validacion['errores'][0], $validacion['errores']);
        }

        $instructorId = $this->resolverInstructor($datos, $usuarioId, $rol);

        if ($instructorId === null) {
            return $this->fallo(400, 'El instructor seleccionado no es válido');
        }

        $id = $this->cursos->crear([
            'titulo' => $validacion['datos']['titulo'],
            'descripcion' => $validacion['datos']['descripcion'],
            'imagen' => $validacion['datos']['imagen'],
            'precio' => $validacion['datos']['precio'],
            'tipo_precio' => $validacion['datos']['tipo_precio'],
            'instructor_id' => $instructorId,
            'categoria_id' => $validacion['datos']['categoria_id'],
            'estatus' => 'activo',
        ]);

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Curso creado correctamente',
            'curso' => $this->detalle($id),
        ];
    }

    public function actualizar(int $cursoId, array $datos, int $usuarioId, string $rol): array
    {
        $curso = $this->cursos->buscarPorId($cursoId);

        if ($curso === null) {
            return $this->fallo(404, 'Curso no encontrado');
        }

        if (!$this->puedeGestionar($curso, $usuarioId, $rol)) {
            return $this->fallo(403, 'No tienes permisos para editar este curso');
        }

        $validacion = $this->validarCurso($datos);

        if ($validacion['errores'] !== []) {
            return $this->fallo(400, $validacion['errores'][0], $validacion['errores']);
        }

        $instructorId = $this->resolverInstructor($datos, $usuarioId, $rol);

        if ($instructorId === null) {
            return $this->fallo(400, 'El instructor seleccionado no es válido');
        }

        $this->cursos->actualizar($cursoId, [
            'titulo' => $validacion['datos']['titulo'],
            'descripcion' => $validacion['datos']['descripcion'],
            'imagen' => $validacion['datos']['imagen'],
            'precio' => $validacion['datos']['precio'],
            'tipo_precio' => $validacion['datos']['tipo_precio'],
            'instructor_id' => $instructorId,
            'categoria_id' => $validacion['datos']['categoria_id'],
        ]);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Curso actualizado correctamente',
            'curso' => $this->detalle($cursoId),
        ];
    }

    public function cambiarEstatus(int $cursoId, string $estatus, int $usuarioId, string $rol): array
    {
        $curso = $this->cursos->buscarPorId($cursoId);

        if ($curso === null) {
            return $this->fallo(404, 'Curso no encontrado');
        }

        if (!$this->puedeGestionar($curso, $usuarioId, $rol)) {
            return $this->fallo(403, 'No tienes permisos para modificar este curso');
        }

        if (!in_array($estatus, self::ESTATUS, true)) {
            return $this->fallo(400, 'estatus debe ser activo o inactivo');
        }

        $this->cursos->cambiarEstatus($cursoId, $estatus);

        return [
            'ok' => true,
            'status' => 200,
            'message' => $estatus === 'activo' ? 'Curso reactivado correctamente' : 'Curso dado de baja correctamente',
            'curso' => $this->detalle($cursoId),
        ];
    }

    public function crearNivel(int $cursoId, array $datos, int $usuarioId, string $rol): array
    {
        $curso = $this->cursos->buscarPorId($cursoId);

        if ($curso === null) {
            return $this->fallo(404, 'Curso no encontrado');
        }

        if (!$this->puedeGestionar($curso, $usuarioId, $rol)) {
            return $this->fallo(403, 'No tienes permisos para administrar los niveles');
        }

        $validacion = $this->validarNivel($datos);

        if ($validacion['errores'] !== []) {
            return $this->fallo(400, $validacion['errores'][0], $validacion['errores']);
        }

        try {
            $id = $this->niveles->crear(array_merge($validacion['datos'], ['curso_id' => $cursoId]));
        } catch (PDOException $exception) {
            return $this->fallo(409, 'Ya existe un nivel con ese orden en el curso');
        }

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Nivel creado correctamente',
            'nivel' => $this->niveles->buscarPorId($id),
        ];
    }

    public function actualizarNivel(int $nivelId, array $datos, int $usuarioId, string $rol): array
    {
        $nivel = $this->niveles->buscarPorId($nivelId);

        if ($nivel === null) {
            return $this->fallo(404, 'Nivel no encontrado');
        }

        $curso = $this->cursos->buscarPorId((int) $nivel['curso_id']);

        if ($curso === null || !$this->puedeGestionar($curso, $usuarioId, $rol)) {
            return $this->fallo(403, 'No tienes permisos para administrar los niveles');
        }

        $validacion = $this->validarNivel($datos);

        if ($validacion['errores'] !== []) {
            return $this->fallo(400, $validacion['errores'][0], $validacion['errores']);
        }

        try {
            $this->niveles->actualizar($nivelId, $validacion['datos']);
        } catch (PDOException $exception) {
            return $this->fallo(409, 'Ya existe un nivel con ese orden en el curso');
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Nivel actualizado correctamente',
            'nivel' => $this->niveles->buscarPorId($nivelId),
        ];
    }

    public function eliminarNivel(int $nivelId, int $usuarioId, string $rol): array
    {
        $nivel = $this->niveles->buscarPorId($nivelId);

        if ($nivel === null) {
            return $this->fallo(404, 'Nivel no encontrado');
        }

        $curso = $this->cursos->buscarPorId((int) $nivel['curso_id']);

        if ($curso === null || !$this->puedeGestionar($curso, $usuarioId, $rol)) {
            return $this->fallo(403, 'No tienes permisos para administrar los niveles');
        }

        if ($this->niveles->tieneProgreso($nivelId)) {
            return $this->fallo(409, 'No se puede eliminar un nivel con progreso registrado');
        }

        $this->niveles->eliminar($nivelId);

        return ['ok' => true, 'status' => 200, 'message' => 'Nivel eliminado correctamente'];
    }

    private function validarCurso(array $datos): array
    {
        $errores = [];
        $titulo = isset($datos['titulo']) && is_string($datos['titulo']) ? trim($datos['titulo']) : '';
        $descripcion = isset($datos['descripcion']) && is_string($datos['descripcion'])
            ? trim($datos['descripcion'])
            : null;
        $imagen = isset($datos['imagen']) && is_string($datos['imagen']) && trim($datos['imagen']) !== ''
            ? trim($datos['imagen'])
            : null;
        $precio = $datos['precio'] ?? 0;
        $tipoPrecio = isset($datos['tipo_precio']) && is_string($datos['tipo_precio'])
            ? $datos['tipo_precio']
            : 'curso';
        $categoriaId = $datos['categoria_id'] ?? null;

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $errores[] = 'titulo es obligatorio y no debe exceder 150 caracteres';
        }

        if (!is_numeric($precio) || (float) $precio < 0 || (float) $precio > 99999999.99) {
            $errores[] = 'precio debe ser un número entre 0 y 99999999.99';
        }

        if (!in_array($tipoPrecio, self::TIPOS_PRECIO, true)) {
            $errores[] = 'tipo_precio debe ser curso o nivel';
        }

        if (!is_numeric($categoriaId) || (int) $categoriaId <= 0 || !$this->categorias->existe((int) $categoriaId)) {
            $errores[] = 'categoria_id no es válida';
        }

        return [
            'errores' => $errores,
            'datos' => [
                'titulo' => $titulo,
                'descripcion' => $descripcion === '' ? null : $descripcion,
                'imagen' => $imagen,
                'precio' => (float) $precio,
                'tipo_precio' => $tipoPrecio,
                'categoria_id' => (int) $categoriaId,
            ],
        ];
    }

    private function validarNivel(array $datos): array
    {
        $errores = [];
        $titulo = isset($datos['titulo']) && is_string($datos['titulo']) ? trim($datos['titulo']) : '';
        $videoUrl = isset($datos['video_url']) && is_string($datos['video_url']) ? trim($datos['video_url']) : '';
        $orden = $datos['orden'] ?? null;
        $precio = $datos['precio'] ?? null;
        $materialUrl = isset($datos['material_url']) && is_string($datos['material_url'])
            ? trim($datos['material_url'])
            : '';
        $materialTipo = isset($datos['material_tipo']) && is_string($datos['material_tipo'])
            ? $datos['material_tipo']
            : '';

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $errores[] = 'titulo es obligatorio y no debe exceder 150 caracteres';
        }

        if ($videoUrl === '' || filter_var($videoUrl, FILTER_VALIDATE_URL) === false) {
            $errores[] = 'video_url debe ser una URL válida';
        }

        if (!ctype_digit((string) $orden) || (int) $orden <= 0) {
            $errores[] = 'orden debe ser un entero positivo';
        }

        if ($precio !== null && (!is_numeric($precio) || (float) $precio < 0)) {
            $errores[] = 'precio debe ser un número mayor o igual a 0';
        }

        if (($materialUrl === '') !== ($materialTipo === '')) {
            $errores[] = 'material_url y material_tipo deben indicarse juntos';
        }

        if ($materialTipo !== '' && !in_array($materialTipo, ['pdf', 'enlace'], true)) {
            $errores[] = 'material_tipo debe ser pdf o enlace';
        }

        return [
            'errores' => $errores,
            'datos' => [
                'titulo' => $titulo,
                'video_url' => $videoUrl,
                'orden' => (int) $orden,
                'precio' => $precio === null || $precio === '' ? null : (float) $precio,
                'material_url' => $materialUrl === '' ? null : $materialUrl,
                'material_tipo' => $materialTipo === '' ? null : $materialTipo,
            ],
        ];
    }

    private function resolverInstructor(array $datos, int $usuarioId, string $rol): ?int
    {
        $instructorId = $usuarioId;

        if ($rol === 'admin' && isset($datos['instructor_id']) && is_numeric($datos['instructor_id'])) {
            $instructorId = (int) $datos['instructor_id'];
        }

        $usuario = $this->usuarios->buscarPorId($instructorId);

        if ($usuario === null || !in_array($usuario['rol'], ['instructor', 'admin'], true)) {
            return null;
        }

        return $instructorId;
    }

    private function puedeGestionar(array $curso, int $usuarioId, string $rol): bool
    {
        return $rol === 'admin' || (int) $curso['instructor_id'] === $usuarioId;
    }

    private function fallo(int $status, string $message, array $detalles = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'detalles' => $detalles !== [] ? $detalles : [$message],
        ];
    }
}
