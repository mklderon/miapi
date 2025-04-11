<?php

return [
    // Clave secreta para firmar los tokens
    'secret_key' => $_ENV['JWT_SECRET'] ?? 'your_secret_key_here',
    
    // Algoritmo de firma (HS256, HS384, HS512, RS256, etc.)
    'algorithm' => 'HS256',
    
    // Tiempo de expiración del token en segundos (1 día por defecto)
    'expiration' => intval($_ENV['JWT_EXPIRATION'] ?? 86400),
    
    // Emisor del token (opcional)
    'issuer' => $_ENV['APP_URL'] ?? 'http://localhost:4321/miapi',
    
    // Audiencia del token (opcional)
    'audience' => $_ENV['APP_URL'] ?? 'http://localhost:4321/miapi',
];