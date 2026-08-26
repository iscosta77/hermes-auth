<?php

declare(strict_types=1);

namespace Hermes\Auth;

use Iscos\Voodoo\Database;
use Iscos\Voodoo\Row;
use RuntimeException;

/**
 * Auth — login, registro e recuperacao de senha para PHP sem framework.
 *
 * password_hash nativo, sessao segura (regenera o id a cada login), mensagens
 * em pt/en/es. Zero dependencias alem de iscos/voodoo-2026 (banco) e
 * iscosta77/validators (validacao de entrada).
 *
 * Uso rapido:
 *   $auth = new Auth($db);
 *   $auth->registrar(['nome' => ..., 'email' => ..., 'senha' => ...]);
 *   $auth->login($email, $senha);       // inicia a sessao
 *   if ($auth->verificar()) { ... }      // rota protegida
 *   $auth->logout();
 */
final class Auth
{
    /** @var array{idioma: string, sessao: string, duracao: int, tabela: string} */
    private array $opcoes;

    private const TEXTOS = [
        'pt' => [
            'email_invalido' => 'E-mail inválido.',
            'email_uso' => 'Este e-mail já está em uso.',
            'nome_curto' => 'Informe o nome completo.',
            'senha_curta' => 'A senha deve ter no mínimo 8 caracteres.',
            'senhas_diferem' => 'As senhas não conferem.',
            'credenciais' => 'E-mail ou senha incorretos.',
            'nao_logado' => 'Você precisa estar logado.',
            'email_nao_encontrado' => 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.',
            'token_invalido' => 'Token inválido ou expirado.',
        ],
        'en' => [
            'email_invalido' => 'Invalid e-mail.',
            'email_uso' => 'This e-mail is already in use.',
            'nome_curto' => 'Please provide the full name.',
            'senha_curta' => 'Password must be at least 8 characters.',
            'senhas_diferem' => 'Passwords do not match.',
            'credenciais' => 'Incorrect e-mail or password.',
            'nao_logado' => 'You must be logged in.',
            'email_nao_encontrado' => 'If the e-mail is registered, we will send the recovery instructions.',
            'token_invalido' => 'Invalid or expired token.',
        ],
        'es' => [
            'email_invalido' => 'Correo electrónico no válido.',
            'email_uso' => 'Este correo ya está en uso.',
            'nome_curto' => 'Indique el nombre completo.',
            'senha_curta' => 'La contraseña debe tener al menos 8 caracteres.',
            'senhas_diferem' => 'Las contraseñas no coinciden.',
            'credenciais' => 'Correo o contraseña incorrectos.',
            'nao_logado' => 'Debe iniciar sesión.',
            'email_nao_encontrado' => 'Si el correo está registrado, enviaremos las instrucciones de recuperación.',
            'token_invalido' => 'Token no válido o caducado.',
        ],
    ];

    /**
     * @param array{idioma?: string, sessao?: string, duracao?: int, tabela?: string} $opcoes
     */
    public function __construct(
        private Database $db,
        array $opcoes = [],
    ) {
        $this->opcoes = array_merge([
            'idioma' => 'pt',
            'sessao' => 'hermes_auth',
            'duracao' => 3600 * 8,   // 8h
            'tabela' => 'hermes_users',
        ], $opcoes);

        if (!isset(self::TEXTOS[$this->opcoes['idioma']])) {
            $this->opcoes['idioma'] = 'pt';
        }
    }

    /** Garante a tabela de usuarios (idempotente; sqlite ou mysql). */
    public function criaTabela(): void
    {
        // anti SQLi em DDL: nome da tabela só com [a-zA-Z0-9_]
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->opcoes['tabela'])) {
            throw new \InvalidArgumentException('Nome de tabela invalido.');
        }
        $driver = $this->db->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->db->run(
                'CREATE TABLE IF NOT EXISTS ' . $this->opcoes['tabela'] . ' (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome VARCHAR(120) NOT NULL,
                    email VARCHAR(190) NOT NULL UNIQUE,
                    senha_hash VARCHAR(255) NOT NULL,
                    criado_em TEXT DEFAULT (datetime(\'now\'))
                )'
            );
            return;
        }

        $this->db->run(
            'CREATE TABLE IF NOT EXISTS ' . $this->opcoes['tabela'] . ' (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                senha_hash VARCHAR(255) NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * Registra um novo usuario.
     *
     * @param array{nome: string, email: string, senha: string, confirmar?: string} $dados
     * @return Row usuario criado
     * @throws RuntimeException em caso de validacao/duplicidade
     */
    public function registrar(array $dados): Row
    {
        $t = self::TEXTOS[$this->opcoes['idioma']];

        $nome = trim((string) ($dados['nome'] ?? ''));
        $email = strtolower(trim((string) ($dados['email'] ?? '')));
        $senha = (string) ($dados['senha'] ?? '');
        $confirmar = $dados['confirmar'] ?? $senha;

        if ($nome === '') {
            throw new RuntimeException($t['nome_curto']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException($t['email_invalido']);
        }
        if (strlen($senha) < 8) {
            throw new RuntimeException($t['senha_curta']);
        }
        if ($senha !== $confirmar) {
            throw new RuntimeException($t['senhas_diferem']);
        }

        $tabela = $this->db->table($this->opcoes['tabela']);
        if ($tabela->where('email', $email)->exists()) {
            throw new RuntimeException($t['email_uso']);
        }

        try {
            $id = $tabela->insert([
                'nome' => $nome,
                'email' => $email,
                'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            ]);
        } catch (\PDOException $e) {
            // corrida (TOCTOU): a constraint UNIQUE protege; vira mensagem amigável
            if ((int) $e->getCode() === 23000) {
                throw new RuntimeException($t['email_uso']);
            }
            throw $e;
        }

        return $tabela->findById($id);
    }

    /** Autentica e inicia a sessao. */
    public function login(string $email, string $senha): Row
    {
        $t = self::TEXTOS[$this->opcoes['idioma']];
        $usuario = $this->db->table($this->opcoes['tabela'])
            ->where('email', strtolower(trim($email)))
            ->findOne();

        if ($usuario === null || !password_verify($senha, (string) $usuario->get('senha_hash'))) {
            throw new RuntimeException($t['credenciais']);
        }

        session_regenerate_id(true);
        $_SESSION[$this->opcoes['sessao']] = [
            'id' => (int) $usuario->get('id'),
            'nome' => (string) $usuario->get('nome'),
            'email' => (string) $usuario->get('email'),
            'expira' => time() + $this->opcoes['duracao'],
        ];

        return $usuario;
    }

    /** Usuario logado (Row) ou null. */
    public function verificar(): ?Row
    {
        $sessao = $_SESSION[$this->opcoes['sessao']] ?? null;
        if (!is_array($sessao) || !isset($sessao['id'], $sessao['expira'])) {
            return null;
        }
        if (time() > (int) $sessao['expira']) {
            $this->logout();
            return null;
        }

        return $this->db->table($this->opcoes['tabela'])->findById((int) $sessao['id']);
    }

    /** Exige autenticacao (lanca se nao logado). */
    public function exigir(): Row
    {
        $usuario = $this->verificar();
        if ($usuario === null) {
            throw new RuntimeException(self::TEXTOS[$this->opcoes['idioma']]['nao_logado']);
        }
        return $usuario;
    }

    public function logout(): void
    {
        // invalida a sessao inteira (anti fixação + limpeza de chaves residuais)
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_regenerate_id(true);
        unset($_SESSION[$this->opcoes['sessao']]);
    }

    /** Troca a senha do usuario logado (ou do id informado). */
    public function trocarSenha(string $senhaAtual, string $novaSenha, ?int $usuarioId = null): void
    {
        $t = self::TEXTOS[$this->opcoes['idioma']];
        $usuarioId ??= (int) ($_SESSION[$this->opcoes['sessao']]['id'] ?? 0);
        $usuario = $this->db->table($this->opcoes['tabela'])->findById($usuarioId);

        if ($usuario === null || !password_verify($senhaAtual, (string) $usuario->get('senha_hash'))) {
            throw new RuntimeException($t['credenciais']);
        }
        if (strlen($novaSenha) < 8) {
            throw new RuntimeException($t['senha_curta']);
        }

        $this->db->table($this->opcoes['tabela'])
            ->where('id', $usuarioId)
            ->update(['senha_hash' => password_hash($novaSenha, PASSWORD_DEFAULT)]);

        // trocou a senha: invalida tokens de recuperacao pendentes
        $this->db->table('hermes_tokens')->where('usuario_id', $usuarioId)->delete();
    }

    /**
     * Gera token de recuperacao de senha (valido por X minutos).
     * O app decide como entregar (email/WhatsApp) e chama redefinirSenha().
     */
    public function esqueciSenha(string $email, int $validadeMinutos = 30): string
    {
        $t = self::TEXTOS[$this->opcoes['idioma']];
        $usuario = $this->db->table($this->opcoes['tabela'])
            ->where('email', strtolower(trim($email)))
            ->findOne();

        if ($usuario === null) {
            // anti enumeração: mensagem genérica (o app decide o que exibir)
            throw new RuntimeException($t['email_nao_encontrado']);
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);   // banco nunca guarda o token cru
        $driver = $this->db->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->db->run(
                'CREATE TABLE IF NOT EXISTS hermes_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id INTEGER NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    tipo VARCHAR(30) NOT NULL,
                    expira_em INTEGER NOT NULL,
                    criado_em TEXT DEFAULT (datetime(\'now\'))
                )'
            );
        } else {
            $this->db->run(
                'CREATE TABLE IF NOT EXISTS hermes_tokens (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT UNSIGNED NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    tipo VARCHAR(30) NOT NULL,
                    expira_em INT UNSIGNED NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
        $this->db->table('hermes_tokens')->insert([
            'usuario_id' => (int) $usuario->get('id'),
            'token' => $tokenHash,
            'tipo' => 'senha',
            'expira_em' => time() + $validadeMinutos * 60,
        ]);

        return $token;
    }

    /** Redefine a senha usando o token gerado por esqueciSenha(). */
    public function redefinirSenha(string $token, string $novaSenha): void
    {
        $t = self::TEXTOS[$this->opcoes['idioma']];
        if (strlen($novaSenha) < 8) {
            throw new RuntimeException($t['senha_curta']);
        }

        $registro = $this->db->table('hermes_tokens')
            ->where('token', hash('sha256', $token))
            ->where('tipo', 'senha')
            ->findOne();

        if ($registro === null || time() > (int) $registro->get('expira_em')) {
            throw new RuntimeException($t['token_invalido']);
        }

        $this->db->table($this->opcoes['tabela'])
            ->where('id', (int) $registro->get('usuario_id'))
            ->update(['senha_hash' => password_hash($novaSenha, PASSWORD_DEFAULT)]);

        // usa o token e invalida TODOS os pendentes do usuario
        $this->db->table('hermes_tokens')->where('usuario_id', (int) $registro->get('usuario_id'))->delete();
    }
}
