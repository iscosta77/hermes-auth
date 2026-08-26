<?php
/**
 * GALERIA — grade com paginação e carousel/lightbox.
 *
 * Ferramentas:
 *  - iscosta77/gallery: Gallery (lê a pasta, monta a grade) + gallery.js/css (lightbox)
 *  - iscosta77/paginator: Paginator (fatia os itens + links de navegação)
 *  - iscosta77/auth: exigir() (rota protegida)
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

use Hermes\Auth\Auth;
use Hermes\Gallery\Gallery;
use Hermes\Paginator\Paginator;

$usuario = (new Auth($config['db']))->exigir();

// iscosta77/gallery: lê a pasta de uploads mostrando SÓ as processadas
// (WebP com marca d'água) — o crop-image grava original + -wm + -wm.webp
$galeria = new Gallery($config['pasta_upload'], [
    'url_base'  => 'public/uploads',
    'extensoes' => ['webp'],        // 1 item por foto (a versão final)
    'legenda'   => false,
]);

$pagina = $_GET['pagina'] ?? 1;
$porPagina = 12;

// iscosta77/paginator: fatia as imagens
$pag = Paginator::paginar($galeria->imagens(), (int) $pagina, $porPagina);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Galeria — Exemplo Completo (gallery + paginator)</title>
<link rel="stylesheet" href="assets/estilo.css">
<link rel="stylesheet" href="assets/gallery.css">
</head>
<body>
<header class="topo">
  <div>
    <strong>📷 Galeria Completa</strong>
    <span class="sub">iscosta77/gallery + iscosta77/paginator</span>
  </div>
  <nav>
    <a href="index.php">Enviar</a>
    <a href="galeria.php" class="ativo">Galeria</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<main class="miolo">
  <h2>Galeria de fotos
    <span class="sub"><?= $pag->total() ?> imagem(ns) · página <?= $pag->pagina() ?>/<?= $pag->totalPaginas() ?> · clique para ampliar</span>
  </h2>

  <?php if (isset($_GET['ok'])): ?><div class="sucesso">✅ <?= (int) ($_GET['total'] ?? 1) ?> foto(s) enviada(s) com recorte, WebP e marca d'água!</div><?php endif; ?>

  <?php if ($pag->total() === 0): ?>
    <div class="box">Nenhuma foto ainda. <a href="index.php">Envie a primeira!</a></div>
  <?php else: ?>
    <?php
    // iscosta77/gallery: grade responsiva (o gallery.js transforma em carousel/lightbox)
    echo $galeria->grade($pag->itens());
    ?>

    <?php
    // iscosta77/paginator: links de navegação
    echo $pag->links('galeria.php?pagina=%d');
    ?>
  <?php endif; ?>
</main>

<script src="assets/gallery.js" defer></script>
</body>
</html>
