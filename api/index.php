<?php

    header("Access-Control-Allow-Origin: https://git.traue.com.br");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    header('Content-Type: application/json');
    $json = file_get_contents('./discs.json');
    echo $json;
?>