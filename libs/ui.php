<?php

/**
 * Funções para interface com o usuário.
 */

/**
 * Imprime conteúdo com quebra de linha ao final.
 * 
 * @param string|int|float $printable
 * @return void
 */
function printnl(string|int|float $printable): void
{
    echo $printable, PHP_EOL;
}

/**
 * Limpa a tela.
 * 
 * @return void
 */
function clear_screen(): void
{
    echo "\x1b[H\x1b[2J";
}

/**
 * Imprime uma linha em branco
 * 
 * @return void
 */
function nl(): void
{
    echo PHP_EOL;
}

/**
 * Imprime um ainformação composta por um rótulo e um valor, alinhando os rótulos à esquerda e os valores à direita.
 * 
 * @param string $label
 * @param string|int|float $value
 * @param string $pad_string
 * @param int $columns
 * @return void
 */
function print_info(string $label, string|int|float $value, int $columns = 80, string $pad_string = ' '): void
{
    $label_width = mb_strlen($label);
    $value_width = mb_strlen($value);
    $pad_width = $columns - $label_width - $value_width;
    if ($pad_width <= 0) {
        throw new \DomainException('$pad_width não pode ser igual ou menor que zero.');
    }
    $pad_str = str_repeat($pad_string, $pad_width);
    printnl($label . $pad_str . $value);
}