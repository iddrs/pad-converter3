<?php

/**
 * 
 * @param string $data
 * @return string
 * @todo
 */
function date_fmt(string $val): ?DateTime
{
    if ($val === '00000000')
        return null;
    return date_create_from_format('dmY', $val);
}

function currency_fmt(string $val): float
{
    return round(intval($val) / 100, 2);
}

function currency_post_signal_fmt(string $val): float
{
    $valor = substr($val, 0, 13);
    $sinal = substr($val, -1, 1);
    return round((int) "$sinal$valor" / 100, 2);
}

function zeros_from_left_to_right(string $val): string
{
    if ($val[0] !== '0')
        return $val;

    $tamanho_original = strlen($val);
    $posicao_primeiro_zero = 0;
    for ($i = 0; $i < strlen($val); $i++) {
        if ($val[$i] !== '0')
            break;
        $posicao_primeiro_zero++;
    }
    $nao_zeros = substr($val, $posicao_primeiro_zero);
    $val = str_pad($nao_zeros, $tamanho_original, '0', STR_PAD_RIGHT);
    return (string) $val;
}

function ndo_fmt(string $val): string
{
    $val = zeros_from_left_to_right($val);
    $val = substr($val, 0, 15);
    return $val;
}

function elemento_fmt(string $val): string
{
    $val = zeros_from_left_to_right($val);
    $val = substr($val, 0, 6);
    return $val;
}

function nro_fmt(string $val): string
{
    $val = zeros_from_left_to_right($val);
    $val = substr($val, 0, 15);
    return $val;
}

function cc_fmt(string $val): string
{
    $val = zeros_from_left_to_right($val);
    $val = substr($val, 0, 15);
    return $val;
}