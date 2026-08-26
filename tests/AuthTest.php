<?php

declare(strict_types=1);

namespace Hermes\Auth\Tests;

use Hermes\Auth\Auth;
use Iscos\Voodoo\Voodoo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthTest extends TestCase
{
    private static Auth $auth;

    public static function setUpBeforeClass(): void
    {
        $db = Voodoo::open('sqlite::memory:');
        $auth = new Auth($db);
        $auth->criaTabela();
        self::$auth = $auth;
    }

    public function testRegistrarCriaUsuario(): void
    {
        $usuario = self::$auth->registrar([
            'nome' => 'Teste',
            'email' => 'teste@exemplo.com',
            'senha' => 'segredo123',
            'confirmar' => 'segredo123',
        ]);

        self::assertSame('teste@exemplo.com', $usuario->get('email'));
    }

    public function testRegistrarEmailDuplicadoLanca(): void
    {
        $this->expectException(RuntimeException::class);
        self::$auth->registrar([
            'nome' => 'Teste 2',
            'email' => 'teste@exemplo.com',
            'senha' => 'segredo123',
        ]);
    }

    public function testRegistrarSenhaCurtaLanca(): void
    {
        $this->expectException(RuntimeException::class);
        self::$auth->registrar([
            'nome' => 'Teste 3',
            'email' => 'teste3@exemplo.com',
            'senha' => '123',
        ]);
    }

    public function testRegistrarEmailInvalidoLanca(): void
    {
        $this->expectException(RuntimeException::class);
        self::$auth->registrar([
            'nome' => 'Teste 4',
            'email' => 'nao-e-email',
            'senha' => 'segredo123',
        ]);
    }

    public function testLoginCorretoIniciaSessao(): void
    {
        $usuario = self::$auth->login('teste@exemplo.com', 'segredo123');

        self::assertSame('teste@exemplo.com', $usuario->get('email'));
        self::assertNotNull(self::$auth->verificar());
    }

    public function testLoginErradoLanca(): void
    {
        $this->expectException(RuntimeException::class);
        self::$auth->login('teste@exemplo.com', 'senha-errada');
    }

    public function testLogoutEncerraSessao(): void
    {
        self::$auth->login('teste@exemplo.com', 'segredo123');
        self::$auth->logout();

        self::assertNull(self::$auth->verificar());
    }

    public function testTrocarSenhaFunciona(): void
    {
        self::$auth->login('teste@exemplo.com', 'segredo123');
        self::$auth->trocarSenha('segredo123', 'novaSenha456');

        // senha antiga falha, nova funciona
        try {
            self::$auth->login('teste@exemplo.com', 'segredo123');
            self::fail('Senha antiga nao deveria funcionar.');
        } catch (RuntimeException) {
            self::assertTrue(true);
        }
        self::$auth->login('teste@exemplo.com', 'novaSenha456');
        self::$auth->trocarSenha('novaSenha456', 'segredo123'); // restaura
    }

    public function testEsqueciSenhaRedefine(): void
    {
        $token = self::$auth->esqueciSenha('teste@exemplo.com');
        self::assertSame(64, strlen($token));

        self::$auth->redefinirSenha($token, 'senhaRedefinida7');
        self::$auth->login('teste@exemplo.com', 'senhaRedefinida7');
        self::$auth->trocarSenha('senhaRedefinida7', 'segredo123');
    }

    public function testTokenInvalidoLanca(): void
    {
        $this->expectException(RuntimeException::class);
        self::$auth->redefinirSenha(bin2hex(random_bytes(32)), 'outraSenha8');
    }

    public function testEmailInexistenteLanca(): void
    {
        $this->expectException(RuntimeException::class);
        self::$auth->esqueciSenha('ninguem@exemplo.com');
    }
}
