<?php
require_once __DIR__ . '/../config/settings.php';

class JWT {
    
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
        $header = self::base64UrlEncode($header);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRATION_TIME;
        $payload = json_encode($payload);
        $payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET_KEY, true);
        $signature = self::base64UrlEncode($signature);
        
        return $header . "." . $payload . "." . $signature;
    }
    
    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET_KEY, true);
        $expectedSignature = self::base64UrlEncode($expectedSignature);
        
        if ($signature !== $expectedSignature) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payload), true);
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
?>