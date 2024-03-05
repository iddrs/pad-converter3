<?php

require 'vendor/autoload.php';

/*
 * Pega os dados do usuário
 */

echo "Conversão dos TXT do PAD.", PHP_EOL;
echo "Digite os dados solicitados.", PHP_EOL;

echo "Ano [AAAA]: ";
fscanf(STDIN, "%d\n", $ano);
echo "Mês [MM] (1 ~ 12): ";
fscanf(STDIN, "%d\n", $mes);
$mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

/*
 * Configurações
 */
//$mes = '01';
//$ano = '2024';
$layout_dir = './layout/2024.01.rev20/';
$source_dirs = [
    "Z:\\Abase\\ARQUIVOSPAD\\$ano\\MES$mes\\",
    "Z:\\Abase\\ARQUIVOSPAD\\$ano\\CAMARA\\MES$mes\\"
];
$remessa = (int) $ano.$mes;


$layoutrepo= new App\Layout\LayoutRepository($layout_dir);
$sourcerepo = new App\Source\SourceRepository(...$source_dirs);
$outputrepo = new \App\Output\OutputRepository('host=localhost port=5432 dbname=pmidd user=postgres password=lise890', $remessa);
$converter = new App\Convert\Converter($layoutrepo, $sourcerepo, $outputrepo, $remessa);
$converter->run();

monta_restos_pagar($remessa, $outputrepo->getConnection());