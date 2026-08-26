<?php
/**
 * PROCESSA UPLOAD — recebe o lote da drop zone e processa cada imagem.
 *
 * Ferramentas:
 *  - iscosta77/auth: exigir()
 *  - iscosta77/upload-multiple: salvarVarios() (lote, valida arquivo a arquivo)
 *  - iscosta77/upload: por trás, move + grava em hermes_uploads
 *  - iscosta77/crop-image: recorte (thumbs), WebP e marca d'água (watermark)
 *  - iscos/voodoo-2026: banco (hermes_uploads + hermes_images)
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

use Hermes\Auth\Auth;
use Hermes\UploadMultiple\UploadMultiple;

(new Auth($config['db']))->exigir();

$upload = new UploadMultiple($config['db'], $config['upload_opcoes']);

try {
    $resultado = $upload->salvarVarios(
        $_FILES['fotos'] ?? [],
        'foto',                          // tipo (hermes_uploads.tipo)
        ['usuario_id' => (int) ($_SESSION['hermes_auth']['id'] ?? 0)],
        $config['processamento'],        // webp + thumbs + watermark
    );

    if ($resultado['total'] > 0) {
        header('Location: galeria.php?ok=1&total=' . $resultado['total']);
    } else {
        header('Location: index.php?erro=1');
    }
    exit;
} catch (RuntimeException $e) {
    header('Location: index.php?erro=' . urlencode($e->getMessage()));
    exit;
}
