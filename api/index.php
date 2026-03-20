<?php

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Vary: Origin");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    header('Content-Type: application/json');
    $json = file_get_contents('./discs.json');
    echo $json;
?>