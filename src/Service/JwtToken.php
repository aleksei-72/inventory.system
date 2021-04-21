<?php


namespace App\Service;


use Firebase\JWT\JWT;

class JwtToken
{

    const jwtKey = 'MIIBOQIBAAJBAMasF6OTRziNcYwoNTb1AsQz+oEtRjq5EZS1ZGvIYkqONu2VCcAa'.
'P00SWcUK67EVTrSJj7jHmoN5HUH2x46H9EcCAwEAAQJAHYa+DKV61EDRO09OeVh4'.
'jMhc1o3v/hI7NqquvgYN9Z5h+eq6kMPpKugWXk1kTXQkHgN3CD6XkVd+VqaSnovz'.
'oQIhAPMr7/3cQGtM8n+rOEZ5XZj+bgSzKa3ZfHydVEKq8Q7zAiEA0ScvY7CV91s9'.
'/eN5ok3jnUOF6R/An5pYu5smHOHx4l0CIGAAG5n8Jw51bVMLtIbWCSaKY8LFPJIe'.
'x2+m6Qn81HgTAiBqEyuPrcGBQD9Cgpnv3Pzxh4tk3nu89nTGQTulLlqU6QIgVxiV'.
'8Doa4t08r2hV44xr01TTyzu5KxIIi8pR/4Mg8R4=';

    const jwtExp = 60*5;//*60*24*365*5;

    public static $globalPayload = array();

    private $payload = array();

    public function __construct() {
        $this->payload = self::$globalPayload;
    }

    public function set(string $name, $value){
        $this->payload[$name] = $value;
        self::$globalPayload[$name] = $value;
    }

    public function get(string $name, $default = null){
        return $this->payload[$name]? $this->payload[$name] : $default;
    }

    public function getPayload() {
        return $this->payload;
    }

    public function generate(): string
    {
        $this->payload['exp'] = time() + self::jwtExp;
        return JWT::encode($this->payload, self::jwtKey);
    }

    public function createFromHeader($header) {
        $this->payload = (array)JWT::decode($header, self::jwtKey, array('HS256'));
        self::$globalPayload = $this->payload;
    }

}