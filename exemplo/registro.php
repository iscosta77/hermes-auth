<?php
/**
 * REGISTRO — iscosta77/auth + iscosta77/validators
 * A validação do formulário é feita pelo Validator (regras declarativas).
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$db = $config['db'];

use Hermes\Auth\Auth;
use Hermes\Validators\Validator;

$erros = [];
$auth = new Auth($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // iscosta77/validators: regras declarativas em string com pipe (pt)
    $v = Validator::make($_POST, [
        'nome'      => 'required|min:3',
        'email'     => 'required|email',
        'senha'     => 'required|min:6',
        'confirmar' => 'required|same:senha',
    ]);

    if ($v->fails()) {
        $erros = $v->errors();
    } else {
        try {
            $auth->registrar($_POST);
            header('Location: login.php?ok=1');
            exit;
        } catch (RuntimeException $e) {
            $erros[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro — Exemplo Completo (iscosta77/auth + validators)</title>
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body class="centro">
<div class="cartao">
  <h1>📝 Criar conta</h1>
  <p class="sub">Validação com <code>iscosta77/validators</code></p>

  <?php foreach ((array) $erros as $e): ?>
    <div class="alerta"><?= htmlspecialchars(is_array($e) ? implode(' ', $e) : $e) ?></div>
  <?php endforeach; ?>

  <form method="post" class="form">
    <label>Nome
      <input type="text" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
    </label>
    <label>E-mail
      <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </label>
    <label>Senha (mín. 6)
      <input type="password" name="senha" required>
    </label>
    <label>Confirmar senha
      <input type="password" name="confirmar" required>
    </label>
    <button type="submit">Criar conta</button>
  </form>
  <p class="sub">Já tem conta? <a href="login.php">Entrar</a></p>
</div>
</body>
</html>
