<?php

namespace Tests\Integration\Repositories;

use PHPUnit\Framework\TestCase;
use App\Repositories\UsuarioRepository;
use App\Helpers\DbHelper;

class UsuarioRepositoryIntegrationTest extends TestCase
{
    private $dbHelper;
    private $usuarioRepository;
    private $testEmail = 'test_integration@example.com';
    private $testPassword = 'test_password';
    private $testUserId;

    protected function setUp(): void
    {
        // Obtenemos la instancia de DbHelper del contenedor de dependencias global
        global $container;
        
        if (!isset($container) || !$container->has('dbHelper')) {
            $this->markTestSkipped('El contenedor de dependencias no está disponible o no tiene dbHelper');
        }
        
        $this->dbHelper = $container->get('dbHelper');
        
        // Instanciar el repositorio con la conexión real
        $this->usuarioRepository = new UsuarioRepository($this->dbHelper);

        // Crear un usuario de prueba
        $this->testUserId = $this->crearUsuarioDePrueba();
    }

    protected function tearDown(): void
    {
        // Eliminar el usuario de prueba
        if ($this->testUserId) {
            try {
                $this->dbHelper->delete('usuarios', [
                    'id_usuario' => $this->testUserId
                ]);
            } catch (\Exception $e) {
                // Registrar el error pero no fallar el test por esto
                error_log('Error al eliminar usuario de prueba: ' . $e->getMessage());
            }
        }
    }

    private function crearUsuarioDePrueba()
    {
        try {
            // Eliminar usuario de prueba si ya existe
            try {
                $this->dbHelper->delete('usuarios', [
                    'email' => $this->testEmail
                ]);
            } catch (\Exception $e) {
                // Ignorar errores si el usuario no existe
            }

            // Insertar usuario de prueba
            $userData = [
                'cedula' => '987654321',
                'nombre' => 'Integration',
                'apellidos' => 'Test',
                'telefono' => '987654321',
                'email' => $this->testEmail,
                'rol' => 'usuario',
                'estado' => 'activo',
                'permiso' => 'basico',
                'password' => password_hash($this->testPassword, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Usar el método insert de DbHelper
            return $this->dbHelper->insert('usuarios', $userData);
        } catch (\Exception $e) {
            $this->markTestSkipped('No se pudo crear el usuario de prueba: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @test
     */
    public function findByEmail_deberia_encontrar_usuario_existente()
    {
        // Verificar que se creó el usuario de prueba
        if (!$this->testUserId) {
            $this->markTestSkipped('No se pudo crear el usuario de prueba');
        }
        
        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->findByEmail($this->testEmail);

        // Verificar el resultado
        $this->assertNotNull($usuario);
        $this->assertEquals($this->testEmail, $usuario['email']);
        $this->assertEquals('Integration', $usuario['nombre']);
        $this->assertEquals('Test', $usuario['apellidos']);
    }

    /**
     * @test
     */
    public function findByEmail_deberia_retornar_null_para_usuario_inexistente()
    {
        // Verificar que se creó el usuario de prueba
        if (!$this->testUserId) {
            $this->markTestSkipped('No se pudo crear el usuario de prueba');
        }
        
        // Ejecutar el método a probar usando un email que definitivamente no existe
        $emailInexistente = 'noexiste_' . uniqid() . '@example.com';
        $usuario = $this->usuarioRepository->findByEmail($emailInexistente);

        // Verificar el resultado
        $this->assertNull($usuario);
    }

    /**
     * @test
     */
    public function verificarCredenciales_deberia_validar_credenciales_correctas()
    {
        // Verificar que se creó el usuario de prueba
        if (!$this->testUserId) {
            $this->markTestSkipped('No se pudo crear el usuario de prueba');
        }
        
        // Ejecutar el método a probar
        $usuario = $this->usuarioRepository->verificarCredenciales($this->testEmail, $this->testPassword);

        // Verificar el resultado
        $this->assertNotNull($usuario);
        $this->assertEquals($this->testEmail, $usuario['email']);
        $this->assertArrayNotHasKey('password', $usuario);
    }

    /**
     * @test
     */
    public function verificarCredenciales_deberia_rechazar_password_incorrecto()
    {
        // Verificar que se creó el usuario de prueba
        if (!$this->testUserId) {
            $this->markTestSkipped('No se pudo crear el usuario de prueba');
        }
        
        // Ejecutar el método a probar con un password incorrecto
        $usuario = $this->usuarioRepository->verificarCredenciales($this->testEmail, 'password_incorrecto');

        // Verificar el resultado
        $this->assertNull($usuario);
    }
}