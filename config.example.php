<?php

// Ukázkový konfigurační soubor generování JWT tokenů
// Pro použití přejmenujte soubor na "config.php"

// Vydavatel JWT tokenu
$issuer = "janch32";

// Uživatelské jméno, které bude v tokenu
$user = $_SERVER['PHP_AUTH_USER'];

// Doba v sekundách, po kterou je token platný
$validity = 3 * 30 * 24 * 60 * 60; // 3 měsíce

// Algoritmus použitý pro podpis
$sign_algo = "RS256";

// Soukromý klíč pro podpis tokenu.
// Je nutné použít minimálně 2048 bitový klíč, doporučeno 4096
$private_key = "<INSERT RSA PRIVATE KEY>";

?>
