<?php
/**
 * Configuração do exemplo completo da família iscosta77.
 * Ajuste o DSN/credenciais para o seu ambiente.
 */

declare(strict_types=1);

// sessão usada pelo iscosta77/auth
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/vendor/autoload.php';

use Iscos\Voodoo\Voodoo;

return [
    // banco (sqlite para teste local simples; troque por mysql:... em produção)
    'db' => Voodoo::open('sqlite:' . __DIR__ . '/banco.sqlite'),

    // pastas do upload (relativas ao exemplo)
    'pasta_upload' => __DIR__ . '/public/uploads',
    'pasta_cache'  => __DIR__ . '/public/cache',
    'pasta_thumbs' => __DIR__ . '/public/thumbs',
    'logo'         => __DIR__ . '/public/logo.png',   // marca d'água

    // regras do upload (iscosta77/upload)
    'upload_opcoes' => [
        'pasta'        => __DIR__ . '/public/uploads',
        'max_tamanho'  => 5 * 1024 * 1024,           // 5MB
        'permitidos'   => ['jpg', 'jpeg', 'png', 'webp'],
        'regras'       => [],                        // regras extras (iscosta77/validators)
    ],

    // processamento de imagem (iscosta77/crop-image)
    'processamento' => [
        'webp'      => 80,                             // gera WebP
        'thumbs'    => [[400, null], [200, 200]],      // thumb largo + quadrado
        'watermark' => [
            'logo'     => __DIR__ . '/public/logo.png',
            'position' => 'bottom-right',
            'margin'   => 10,
            'opacity'  => 80,
            'scale'    => 15,                          // % da largura
        ],
        'cache_dir' => __DIR__ . '/public/cache',
    ],
];
