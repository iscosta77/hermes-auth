<?php
/**
 * LOGIN — iscosta77/auth
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$db = $config['db'];

use Hermes\Auth\Auth;

$erro = '';
$auth = new Auth($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $auth->login((string) ($_POST['email'] ?? ''), (string) ($_POST['senha'] ?? ''));
        header('Location: index.php');
        exit;
    } catch (RuntimeException $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Exemplo Completo (iscosta77/auth)</title>
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body class="centro">
<div class="cartao">
  <h1>📷 Galeria Completa</h1>
  <p class="sub">Exemplo com todas as ferramentas da família iscosta77</p>

  <?php if ($erro): ?><div class="alerta"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <form method="post" class="form">
    <label>E-mail
      <input type="email" name="email" required placeholder="admin@exemplo.com">
    </label>
    <label>Senha
      <input type="password" name="senha" required placeholder="••••••••">
    </label>
    <button type="submit">Entrar</button>
  </form>
  <p class="sub">Primeira vez? <a href="registro.php">Crie sua conta</a> · admin padrão: admin@exemplo.com / admin123</p>
</div>
</body>
</html>
