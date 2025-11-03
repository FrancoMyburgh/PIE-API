<?php

class Auth{

    private $token = "7e7fe8897ec74c80b7151a3b347772933396ca9a56b80a061b1b4fec33e223ba";

    public function authenticate(){

        $headers = getallheaders();
        if(!array_key_exists('Authorization', $headers)){
            header('HTTP/1.0 400');
            echo json_encode(["error" => "Auth header missing"]);
            exit();
        }

        if(substr($headers['Authorization'], 0, 6) !== 'Bearer'){

            header("HTTP/1.0 400");
            echo json_encode(["error" => "Bearer keyword missing"]);
            exit();

        }

        $bearerToken = trim(substr($headers['Authorization'], 7));

        return $bearerToken === $this->token;

    }

}

?>