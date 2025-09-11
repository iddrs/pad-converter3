<?php
use PadConverter\Benchmark;
use PadConverter\Convert\Converter;
use PadConverter\Layout\LayoutRepository;
use PadConverter\Output\OutputRepository;
use PadConverter\Source\SourceRepository;

setlocale(LC_ALL, 'pt_BR');

require_once 'vendor/autoload.php';

// Limpa a tela
clear_screen();

// Informações do programa
printnl('PAD CONVERTER 4');
printnl('Conversor de dados dos *.txt do SIAPC/PAD do TCE/RS');
printnl('Copyright by Everton da Rosa (2025)');
nl();

// Carrega as configurações do .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
printnl('Configurações carregadas.');

printnl('Preparando repositório de origem dos dados...');
$srepo = new SourceRepository($_ENV['SOURCE_DIR']);

printnl('Lendo metadados dos arquivos...');
$srepo->readSourceMetadata();

print_info("Total de arquivos para processar: ", $srepo->totalFilesToProcess, 50);
print_info("Total de linhas para processar: ", $srepo->totalLinesToProcess, 50);
print_info("Remessa: ", $srepo->remessa, 50);

nl();

printnl('Deseja prosseguir? [S]im / [N]ão');
echo '> ';
$confirm = trim(fgets(STDIN));
switch (strtolower($confirm)) {
    case 's':
        break;
    case 'n':
        exit();
    default:
        throw new \DomainException('Apenas S, s, N e n são permitidos.');
}


print_info('Início da conversão', date('h:i:s'));
Benchmark::start();

printnl('Preparando repositório de layouts...');
$lrepo = new LayoutRepository($_ENV['LAYOUT_DIR']);

printnl('Preparando repositório de destino dos dados convertidos...');
$orepo = new OutputRepository($_ENV['CONNECTION_STRING'], $srepo->remessa);

printnl('Iniciando a conversão...');
$converter = new Converter($lrepo, $srepo, $orepo, $srepo->remessa)->run();

printnl('Iniciando a montagem dos restos a pagar...');
monta_restos_pagar($srepo->remessa, $orepo->getConnection());

print_info('Término da conversão', date('h:i:s'));
print_info('Tempo decorrido', Benchmark::stop());