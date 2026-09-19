$ErrorActionPreference = 'Stop'

$root = 'soscur-portal'

$directories = @(
    "$root\app",
    "$root\app\config",
    "$root\app\controllers",
    "$root\app\models",
    "$root\app\views",
    "$root\app\views\auth",
    "$root\app\views\courses",
    "$root\app\views\dashboard",
    "$root\app\views\templates",
    "$root\app\helpers",
    "$root\public",
    "$root\public\css",
    "$root\public\js",
    "$root\public\uploads"
)

$files = @(
    "$root\app\config\database.php",
    "$root\app\config\config.php",
    "$root\app\controllers\AuthController.php",
    "$root\app\controllers\CourseController.php",
    "$root\app\controllers\UserController.php",
    "$root\app\controllers\ApiController.php",
    "$root\app\models\User.php",
    "$root\app\models\Course.php",
    "$root\app\models\Level.php",
    "$root\app\models\Enrollment.php",
    "$root\app\models\Certificate.php",
    "$root\app\views\auth\login.php",
    "$root\app\views\auth\register.php",
    "$root\app\views\courses\index.php",
    "$root\app\views\courses\detail.php",
    "$root\app\views\dashboard\student.php",
    "$root\app\views\dashboard\instructor.php",
    "$root\app\views\templates\header.php",
    "$root\app\views\templates\footer.php",
    "$root\app\views\diploma.php",
    "$root\app\helpers\Session.php",
    "$root\app\helpers\SendGridHelper.php",
    "$root\public\index.php",
    "$root\README.md",
    "$root\schema.sql"
)

$gitkeeps = @(
    "$root\public\css\.gitkeep",
    "$root\public\js\.gitkeep",
    "$root\public\uploads\.gitkeep"
)

foreach ($dir in $directories) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}

foreach ($file in $files) {
    New-Item -ItemType File -Path $file -Force | Out-Null
}

foreach ($gk in $gitkeeps) {
    New-Item -ItemType File -Path $gk -Force | Out-Null
}

$gitignore = @'
.DS_Store
Thumbs.db
Desktop.ini

.vs/
.vscode/
.idea/
*.suo
*.user
*.userosscache
*.sln.docstates
*.csproj.user

/vendor/
composer.phar
composer.lock

.env
.env.*
!.env.example
*.key
*.pem
secrets/

*.log
/logs/
/tmp/
/cache/

# Carpeta de subidas versionada solo con su .gitkeep
/public/uploads/*
!/public/uploads/.gitkeep

*.bak
*~
'@

$htaccess = @'
Options -Indexes

DirectoryIndex index.php

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Archivos y carpetas reales (css, js, img) se sirven directo
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # El resto va al front controller conservando la query string
    RewriteRule ^ index.php [QSA,L]
</IfModule>

<FilesMatch "\.(bak|orig|save)$">
    Require all denied
</FilesMatch>
'@

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Join-Path $root '.gitignore'), $gitignore, $utf8NoBom)
[System.IO.File]::WriteAllText((Join-Path $root 'public\.htaccess'), $htaccess, $utf8NoBom)