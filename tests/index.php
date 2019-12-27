<?php

// Carregando autoload do composer
require __DIR__ . '/../vendor/autoload.php';

// Utilizando a classe
use Source\Correios\CorreiosWS;

$retorno = (new CorreiosWS())
    ->boot('04014', '13631009', '09951420', '1.5', 1, 18, 10, 10, 0)
    ->calcPrecoPrazo()
    ->toArray();

// Debug retornar array ou json
if (is_string($retorno)) {
    echo $retorno;
} else {
    var_dump($retorno);
}
