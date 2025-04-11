<?php

namespace Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use App\Repositories\UsuarioRepository;
use App\Helpers\DbHelper;

class UsuarioRepositoryTest extends TestCase
{
    private $dbHelperMock;
    private $usuarioRepository;

    protected function setUp(): void
    {
        // Crear un mock de DbHelper
        $this->dbHelperMock = $this->createMock(DbHelper::class);
        
        // Instanciar el repositorio con el mock
        $this->usuarioRepository = new UsuarioRepository($this->dbHelperMock);
    }

    /**
     * @test
     */
    public function findByEmail_deberia_retornar_usuario_cuando_existe()
    {
        // Datos de prueba
        $email = 'test@example.com';
        $usuarioEsperado = [
            'id_usuario' => 1,
            'cedula' => '123456789',
            'nombre' => 'Test',
            'apellidos' => 'User',
            'telefono' => '123456789',
            'email' => 'test@example.com',
            'rol' => 'usuario',
            'estado' => 'activo',
            'permiso' => 'basico',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ];

        // Configurar el comportamiento del mock
        $this->dbHelperMock->expects($this->once())
            ->method('find')
            ->with(
                $this->equalTo('usuarios'),
                $this->anything(),
                $this->equalTo(['email' => $email])
            )
            ->willReturn($usuarioEsperado);

        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->findByEmail($email);

        // Verificar el resultado
        $this->assertEquals($usuarioEsperado, $usuario);
    }

    /**
     * @test
     */
    public function findByEmail_deberia_retornar_null_cuando_no_existe()
    {
        // Datos de prueba
        $email = 'noexiste@example.com';

        // Configurar el comportamiento del mock
        $this->dbHelperMock->expects($this->once())
            ->method('find')
            ->with(
                $this->equalTo('usuarios'),
                $this->anything(),
                $this->equalTo(['email' => $email])
            )
            ->willReturn(null);

        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->findByEmail($email);

        // Verificar el resultado
        $this->assertNull($usuario);
    }

    /**
     * @test
     */
    public function verificarCredenciales_deberia_retornar_usuario_sin_password_cuando_credenciales_son_validas()
    {
        // Datos de prueba
        $email = 'test@example.com';
        $password = 'password123';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $usuarioEnBD = [
            'id_usuario' => 1,
            'cedula' => '123456789',
            'nombre' => 'Test',
            'apellidos' => 'User',
            'telefono' => '123456789',
            'email' => $email,
            'rol' => 'usuario',
            'estado' => 'activo',
            'permiso' => 'basico',
            'password' => $passwordHash,
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ];
        
        $usuarioEsperado = $usuarioEnBD;
        unset($usuarioEsperado['password']);

        // Configurar el comportamiento del mock
        $this->dbHelperMock->expects($this->once())
            ->method('find')
            ->willReturn($usuarioEnBD);

        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->verificarCredenciales($email, $password);

        // Verificar el resultado
        $this->assertEquals($usuarioEsperado, $usuario);
        $this->assertArrayNotHasKey('password', $usuario);
    }

    /**
     * @test
     */
    public function verificarCredenciales_deberia_retornar_null_cuando_usuario_no_existe()
    {
        // Datos de prueba
        $email = 'noexiste@example.com';
        $password = 'password123';

        // Configurar el comportamiento del mock
        $this->dbHelperMock->expects($this->once())
            ->method('find')
            ->willReturn(null);

        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->verificarCredenciales($email, $password);

        // Verificar el resultado
        $this->assertNull($usuario);
    }

    /**
     * @test
     */
    public function verificarCredenciales_deberia_retornar_null_cuando_password_incorrecto()
    {
        // Datos de prueba
        $email = 'test@example.com';
        $passwordCorrecto = 'password123';
        $passwordIncorrecto = 'wrongpassword';
        $passwordHash = password_hash($passwordCorrecto, PASSWORD_DEFAULT);
        
        $usuarioEnBD = [
            'id_usuario' => 1,
            'cedula' => '123456789',
            'nombre' => 'Test',
            'apellidos' => 'User',
            'telefono' => '123456789',
            'email' => $email,
            'rol' => 'usuario',
            'estado' => 'activo',
            'permiso' => 'basico',
            'password' => $passwordHash,
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ];

        // Configurar el comportamiento del mock
        $this->dbHelperMock->expects($this->once())
            ->method('find')
            ->willReturn($usuarioEnBD);

        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->verificarCredenciales($email, $passwordIncorrecto);

        // Verificar el resultado
        $this->assertNull($usuario);
    }
}