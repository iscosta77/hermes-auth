<?php
/**
 * INSTALL — cria o banco, as tabelas e o usuário admin.
 *
 * Ferramentas usadas:
 *  - iscos/voodoo-2026: Database/Table (banco)
 *  - iscosta77/auth:    criaTabela() + registrar()
 *  - iscosta77/upload:  UploadRepository->criaTabela() (hermes_uploads)
 *  - iscosta77/crop-image: ImageRepository->criaTabela() (hermes_images)
 *
 * Rode uma vez: php install.php
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

use Hermes\Auth\Auth;
use Hermes\CropImage\ImageRepository;
use Hermes\Upload\UploadRepository;

$db = $config['db'];

echo "== Criando tabelas (voodoo-2026 + família) ==\n";
(new Auth($db))->criaTabela();                 // hermes_users
(new UploadRepository($db))->criaTabela();     // hermes_uploads
(new ImageRepository($db))->criaTabela();      // hermes_images
echo "  hermes_users, hermes_uploads, hermes_images OK\n";

echo "== Criando pastas de upload ==\n";
foreach ([$config['pasta_upload'], $config['pasta_cache'], $config['pasta_thumbs']] as $pasta) {
    if (!is_dir($pasta)) {
        mkdir($pasta, 0775, true);
        echo "  criada: " . basename($pasta) . "\n";
    }
}

echo "== Usuário admin (iscosta77/auth) ==\n";
$auth = new Auth($db);
try {
    $auth->registrar([
        'nome' => 'Administrador',
        'email' => 'admin@exemplo.com',
        'senha' => 'admin123',
        'confirmar' => 'admin123',
    ]);
    echo "  criado: admin@exemplo.com / admin123\n";
} catch (RuntimeException $e) {
    echo "  (já existe ou erro): " . $e->getMessage() . "\n";
}

echo "\nPronto! Rode: php -S localhost:8000 -t .  →  http://localhost:8000/login.php\n";
