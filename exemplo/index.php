<?php
/**
 * INDEX — área logada com DROP ZONE de upload múltiplo.
 *
 * Ferramentas:
 *  - iscosta77/auth: exigir() (rota protegida)
 *  - iscosta77/upload-multiple: drop zone + envio em lote (form abaixo)
 *  - iscosta77/crop-image: recorte/WebP/marca d'água (no processa_upload.php)
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

use Hermes\Auth\Auth;

$usuario = (new Auth($config['db']))->exigir(); // 401 se não logado
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Enviar fotos — Exemplo Completo</title>
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body>
<header class="topo">
  <div>
    <strong>📷 Galeria Completa</strong>
    <span class="sub">família iscosta77 — <?= htmlspecialchars($usuario->get('nome')) ?></span>
  </div>
  <nav>
    <a href="index.php" class="ativo">Enviar</a>
    <a href="galeria.php">Galeria</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<main class="miolo">
  <h2>Enviar fotos <span class="sub">(recorte + WebP + marca d'água automáticos)</span></h2>

  <?php if (isset($_GET['ok'])): ?><div class="sucesso">✅ Upload concluído!</div><?php endif; ?>

  <form id="form-upload" action="processa_upload.php" method="post" enctype="multipart/form-data" class="dropzone">
    <input type="file" name="fotos[]" id="fotos" multiple accept="image/*" hidden>
    <div class="dz-mensagem">
      <strong>Arraste as fotos aqui</strong> ou <button type="button" id="btn-escolher" class="link">clique para escolher</button>
      <p class="sub">JPG/PNG/WebP · até 5MB · várias de uma vez</p>
    </div>
    <ul id="dz-lista" class="dz-lista"></ul>
    <button type="submit" id="btn-enviar" disabled>Enviar fotos</button>
  </form>
</main>

<script>
// Drop zone em JS puro (mesma ideia do iscosta77/upload-multiple)
(function () {
  const input = document.getElementById('fotos');
  const lista = document.getElementById('dz-lista');
  const btnEnviar = document.getElementById('btn-enviar');
  const zona = document.querySelector('.dropzone');
  let arquivos = [];

  function renderizar() {
    lista.innerHTML = '';
    arquivos.forEach((f, i) => {
      const li = document.createElement('li');
      const mini = URL.createObjectURL(f);
      li.innerHTML = '<img src="' + mini + '"> <span>' + f.name + ' (' + (f.size / 1024).toFixed(0) + ' KB)</span>';
      const btn = document.createElement('button');
      btn.type = 'button'; btn.textContent = '×'; btn.className = 'dz-remover';
      btn.onclick = () => { arquivos.splice(i, 1); renderizar(); };
      li.appendChild(btn);
      lista.appendChild(li);
    });
    btnEnviar.disabled = arquivos.length === 0;
  }

  function adicionar(listaNova) {
    arquivos = arquivos.concat(Array.from(listaNova).filter(f => f.type.startsWith('image/')));
    renderizar();
  }

  input.addEventListener('change', () => adicionar(input.files));
  document.getElementById('btn-escolher').addEventListener('click', () => input.click());
  zona.addEventListener('dragover', e => { e.preventDefault(); zona.classList.add('arrastando'); });
  zona.addEventListener('dragleave', () => zona.classList.remove('arrastando'));
  zona.addEventListener('drop', e => {
    e.preventDefault();
    zona.classList.remove('arrastando');
    adicionar(e.dataTransfer.files);
  });
  btnEnviar.addEventListener('click', () => {
    const dt = new DataTransfer();
    arquivos.forEach(f => dt.items.add(f));
    input.files = dt.files;
  });
})();
</script>
</body>
</html>
