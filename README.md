# Hermes Auth (iscosta77/auth)

Login, registro e recuperação de senha para PHP sem framework — `password_hash`
nativo, sessão segura (regenera o id a cada login), mensagens em **pt/en/es**.
Integra com `iscos/voodoo-2026` (banco) e `iscosta77/validators`.

## Instalação

```bash
composer require iscosta77/auth
```

## Uso

```php
use Hermes\Auth\Auth;

$auth = new Auth($db);            // $db = Iscos\Voodoo\Database
$auth->criaTabela();              // cria hermes_users (idempotente)

// Registro
$auth->registrar([
    'nome' => 'Maria',
    'email' => 'maria@exemplo.com',
    'senha' => 'segredo123',
    'confirmar' => 'segredo123',
]);

// Login / sessão
$auth->login($email, $senha);
if ($auth->verificar()) { /* rota protegida */ }
$auth->exigir();                  // lança se não logado
$auth->logout();

// Senha
$auth->trocarSenha($atual, $nova);
$token = $auth->esqueciSenha($email);          // 64 chars, 30min
$auth->redefinirSenha($token, $novaSenha);     // o app entrega o token
```

## Opções

| Opção | Padrão | Descrição |
|---|---|---|
| `idioma` | `pt` | `pt` \| `en` \| `es` (mensagens de erro) |
| `sessao` | `hermes_auth` | Chave na `$_SESSION` |
| `duracao` | `28800` | Validade da sessão em segundos (8h) |
| `tabela` | `hermes_users` | Nome da tabela de usuários |

## Segurança

- `password_hash`/`password_verify` (bcrypt/argon2 do PHP)
- `session_regenerate_id(true)` a cada login
- Token de recuperação aleatório (64 hex) com validade

## Testes

```bash
composer test
```

## Licença

MIT — criado e mantido por **Hermes Agent (Nous Research)**, publicado por
**Ildefonso Costa**.
