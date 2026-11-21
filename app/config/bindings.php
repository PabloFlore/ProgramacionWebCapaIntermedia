<?php

declare(strict_types=1);

use App\Config\Database;
use App\Core\Container;
use App\Middleware\AccessMiddlewareFactory;
use App\Middleware\Contracts\AccessMiddlewareFactoryInterface;
use App\Middleware\Contracts\CorsMiddlewareFactoryInterface;
use App\Middleware\Contracts\CsrfApiMiddlewareInterface;
use App\Middleware\CorsMiddlewareFactory;
use App\Middleware\CsrfApiMiddleware;
use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Contracts\CategoriaRepositoryInterface;
use App\Models\Contracts\ComentarioRepositoryInterface;
use App\Models\Contracts\CursoRepositoryInterface;
use App\Models\Contracts\DiplomaRepositoryInterface;
use App\Models\Contracts\InscripcionRepositoryInterface;
use App\Models\Contracts\MensajeRepositoryInterface;
use App\Models\Contracts\MetodoPagoRepositoryInterface;
use App\Models\Contracts\NivelRepositoryInterface;
use App\Models\Contracts\OrdenRepositoryInterface;
use App\Models\Contracts\PagoRepositoryInterface;
use App\Models\Contracts\ProgresoNivelRepositoryInterface;
use App\Models\Contracts\ReporteRepositoryInterface;
use App\Models\Contracts\UsuarioRepositoryInterface;
use App\Models\Curso;
use App\Models\Diploma;
use App\Models\Inscripcion;
use App\Models\Mensaje;
use App\Models\MetodoPago;
use App\Models\Nivel;
use App\Models\Orden;
use App\Models\Pago;
use App\Models\ProgresoNivel;
use App\Models\Reporte;
use App\Models\Usuario;
use App\Services\AuthService;
use App\Services\CategoriaService;
use App\Services\ComentarioService;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\CategoriaServiceInterface;
use App\Services\Contracts\ComentarioServiceInterface;
use App\Services\Contracts\CourseServiceInterface;
use App\Services\Contracts\DiplomaServiceInterface;
use App\Services\Contracts\InscripcionServiceInterface;
use App\Services\Contracts\KardexServiceInterface;
use App\Services\Contracts\MailerInterface;
use App\Services\Contracts\MensajeServiceInterface;
use App\Services\Contracts\PagoServiceInterface;
use App\Services\Contracts\ReporteServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\CourseService;
use App\Services\DiplomaService;
use App\Services\InscripcionService;
use App\Services\KardexService;
use App\Services\MensajeService;
use App\Services\PagoService;
use App\Services\ReporteService;
use App\Services\SendGridMailer;
use App\Services\UserService;

return static function (Container $container): void {
    $container->singleton(PDO::class, static fn (): PDO => Database::getConnection());

    $repositories = [
        CategoriaRepositoryInterface::class => Categoria::class,
        ComentarioRepositoryInterface::class => Comentario::class,
        CursoRepositoryInterface::class => Curso::class,
        DiplomaRepositoryInterface::class => Diploma::class,
        InscripcionRepositoryInterface::class => Inscripcion::class,
        MensajeRepositoryInterface::class => Mensaje::class,
        MetodoPagoRepositoryInterface::class => MetodoPago::class,
        NivelRepositoryInterface::class => Nivel::class,
        OrdenRepositoryInterface::class => Orden::class,
        PagoRepositoryInterface::class => Pago::class,
        ProgresoNivelRepositoryInterface::class => ProgresoNivel::class,
        ReporteRepositoryInterface::class => Reporte::class,
        UsuarioRepositoryInterface::class => Usuario::class,
    ];

    foreach ($repositories as $abstract => $concrete) {
        $container->singleton(
            $abstract,
            static fn (Container $c): object => $c->get($concrete)
        );
    }

    $services = [
        AuthServiceInterface::class => AuthService::class,
        CategoriaServiceInterface::class => CategoriaService::class,
        ComentarioServiceInterface::class => ComentarioService::class,
        CourseServiceInterface::class => CourseService::class,
        DiplomaServiceInterface::class => DiplomaService::class,
        InscripcionServiceInterface::class => InscripcionService::class,
        KardexServiceInterface::class => KardexService::class,
        MensajeServiceInterface::class => MensajeService::class,
        PagoServiceInterface::class => PagoService::class,
        ReporteServiceInterface::class => ReporteService::class,
        UserServiceInterface::class => UserService::class,
    ];

    foreach ($services as $abstract => $concrete) {
        $container->singleton(
            $abstract,
            static fn (Container $c): object => $c->get($concrete)
        );
    }

    $container->singleton(
        MailerInterface::class,
        static fn (): SendGridMailer => new SendGridMailer()
    );

    $container->singleton(
        AccessMiddlewareFactoryInterface::class,
        static fn (): AccessMiddlewareFactory => new AccessMiddlewareFactory()
    );
    $container->singleton(
        CorsMiddlewareFactoryInterface::class,
        static fn (): CorsMiddlewareFactory => new CorsMiddlewareFactory()
    );
    $container->singleton(
        CsrfApiMiddlewareInterface::class,
        static fn (): CsrfApiMiddleware => new CsrfApiMiddleware()
    );
};
