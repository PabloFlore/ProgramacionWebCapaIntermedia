<?php

declare(strict_types=1);

use App\Config\Database;
use App\Core\Container;
use App\Models\Contracts\UsuarioRepositoryInterface;
use App\Models\Usuario;
use App\Services\Contracts\MailerInterface;
use App\Services\SendGridMailer;

return static function (Container $container): void {
    $container->singleton(PDO::class, static fn (): PDO => Database::getConnection());
    $container->singleton(
        UsuarioRepositoryInterface::class,
        static fn (Container $c): Usuario => $c->get(Usuario::class)
    );
    $container->singleton(
        MailerInterface::class,
        static fn (): SendGridMailer => new SendGridMailer()
    );
};
