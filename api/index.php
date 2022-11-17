<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *'); 
    $json = file_get_contents('./discs.json');
    echo $json;
?>