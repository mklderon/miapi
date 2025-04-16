<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Clase para manejar operaciones con tokens JWT
 */
class JwtHelper
{
    /**
     * Configuración de JWT
     *
     * @var array
     */
    private $config;
    
    /**
     * Constructor
     *
     * @param array $config Configuración de JWT
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Genera un token JWT para un usuario
     *
     * @param array $userData Datos del usuario
     * @return string Token JWT generado
     */
    public function generateToken(array $userData): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + $this->config['expiration'];
        
        $payload = [
            'iat' => $issuedAt,         // Tiempo de emisión
            'exp' => $expiration,       // Tiempo de expiración
            'iss' => $this->config['issuer'],  // Emisor
            'aud' => $this->config['audience'], // Audiencia
            'data' => $userData['id_usuario']         // Datos del usuario por el momento solo el id
        ];
        
        return JWT::encode($payload, $this->config['secret_key'], $this->config['algorithm']);
    }
    
    /**
     * Valida y decodifica un token JWT
     *
     * @param string $token Token JWT a validar
     * @return array|null Payload decodificado o null si es inválido
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config['secret_key'], $this->config['algorithm']));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            // Token expirado
            return null;
        } catch (SignatureInvalidException $e) {
            // Firma inválida
            return null;
        } catch (BeforeValidException $e) {
            // Token no válido aún
            return null;
        } catch (DomainException | InvalidArgumentException | UnexpectedValueException $e) {
            // Otros errores
            return null;
        }
    }
    
    /**
     * Extrae el token de la cabecera de autorización
     *
     * @param string $authHeader Cabecera de autorización
     * @return string|null Token extraído o null
     */
    public function extractTokenFromHeader(?string $authHeader): ?string
    {
        if (empty($authHeader)) {
            return null;
        }
        
        // Verificar si el formato es "Bearer {token}"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}