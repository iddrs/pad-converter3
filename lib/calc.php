<?php

/**
 * 
 * @param string $data
 * @return string
 * @todo
 */

function detect_entidade(array $row): ?string {
    // pega a entidade pelo código do órgão
    if (key_exists('orgao', $row)) {
        switch ($row['orgao']){
            case 1:
                return 'cm';
            case 12:
            case 50:
                return 'fpsm';
            default:
                return 'pm';
        }
    }
    
    // pega a entidade se o cnpj dos dados for da câmara
    if (key_exists('cnpj', $row)){
        if($row['cnpj'] === '12292535000162') return 'cm';
    }
        
    // pega a entidade pela entidade do empenho
    if (key_exists('entidade_empenho', $row)) {
        switch ($row['entidade_empenho']){
            case 0:
                return 'pm';
            case 1:
                return 'fpsm';
            case 2:
                return 'cm';
        }
    }
    
    // pega a entidade pela fonte de recurso
    // só consegue separar o fpsm do resto.
    // basicamente serve para o decreto.txt
    if (key_exists('fonte_recurso_suplementacao', $row)){
        switch ($row['fonte_recurso_suplementacao']){
            case 800:
            case 801:
            case 802:
            case 803:
                return 'fpsm';
        }
    }
    if (key_exists('fonte_recurso_reducao', $row)){
        switch ($row['fonte_recurso_reducao']){
            case 800:
            case 801:
            case 802:
            case 803:
                return 'fpsm';
            default :
                return 'pm';
        }
    }
    
}

function remessa(array $row): int {
    return 0;
}

function natureza_receita(array $row): string {
    switch($row['codigo_receita'][0]){
        case 9:
            return nro_fmt(substr($row['codigo_receita'], 1).'0');
        case 7:
            return nro_fmt('1'.substr($row['codigo_receita'], 1));
        case 8:
            return nro_fmt('2'.substr($row['codigo_receita'], 1));
        default:
            return (string) nro_fmt($row['codigo_receita']);
    }
}

function categoria_receita(array $row): string {
    switch($row['codigo_receita'][0]){
        case 9:
            return 'dedutora';
        case 7:
        case 8:
            return 'intra';
        default:
            return 'normal';
    }
}

function tipo_receita(array $row): int {
    return (int) substr($row['natureza_receita'], 7, 1);
}

function a_arrecadar_atualizado(array $row): float {
    return (float) round(($row['previsao_atualizada'] - $row['receita_realizada']), 2);
}

function a_arrecadar_orcado(array $row): float {
    return (float) round(($row['receita_orcada'] - $row['receita_realizada']), 2);
}

function realizada_1bim(array $row): float {
    return (float) round(($row['realizada_jan'] + $row['realizada_fev']), 2);
}

function realizada_2bim(array $row): float {
    return (float) round(($row['realizada_mar'] + $row['realizada_abr']), 2);
}

function realizada_3bim(array $row): float {
    return (float) round(($row['realizada_mai'] + $row['realizada_jun']), 2);
}

function realizada_4bim(array $row): float {
    return (float) round(($row['realizada_jul'] + $row['realizada_ago']), 2);
}

function realizada_5bim(array $row): float {
    return (float) round(($row['realizada_set'] + $row['realizada_out']), 2);
}

function realizada_6bim(array $row): float {
    return (float) round(($row['realizada_nov'] + $row['realizada_dez']), 2);
}

function meta_jan(array $row): float {
    return (float) round(($row['meta_1bim']/2), 2);
}

function meta_fev(array $row): float {
    return (float) round(($row['meta_1bim']/2), 2);
}

function meta_mar(array $row): float {
    return (float) round(($row['meta_2bim']/2), 2);
}

function meta_abr(array $row): float {
    return (float) round(($row['meta_2bim']/2), 2);
}

function meta_mai(array $row): float {
    return (float) round(($row['meta_3bim']/2), 2);
}

function meta_jun(array $row): float {
    return (float) round(($row['meta_3bim']/2), 2);
}

function meta_jul(array $row): float {
    return (float) round(($row['meta_4bim']/2), 2);
}

function meta_ago(array $row): float {
    return (float) round(($row['meta_4bim']/2), 2);
}

function meta_set(array $row): float {
    return (float) round(($row['meta_5bim']/2), 2);
}

function meta_out(array $row): float {
    return (float) round(($row['meta_5bim']/2), 2);
}

function meta_nov(array $row): float {
    return (float) round(($row['meta_6bim']/2), 2);
}

function meta_dez(array $row): float {
    return (float) round(($row['meta_6bim']/2), 2);
}

function dotacao_atualizada(array $row): float {
    return (float) round(($row['dotacao_inicial'] + $row['atualizacao_monetaria'] + $row['credito_suplementar'] + $row['credito_especial'] + $row['credito_extraordinario'] - $row['reducao_dotacao'] + $row['transferencia'] + $row['transposicao'] + $row['remanejamento']), 2);
}

function dotacao_disponivel(array $row): float {
    return (float) round(($row['dotacao_atualizada'] - $row['valor_limitado'] + $row['valor_recomposto']), 2);
}

function saldo_a_empenhar(array $row): float {
    return (float) round(($row['dotacao_atualizada'] - $row['valor_empenhado']), 2);
}

function saldo_disponivel(array $row): float {
    return (float) round(($row['dotacao_disponivel'] - $row['valor_empenhado']), 2);
}

function empenhado_a_liquidar(array $row): float {
    return (float) round(($row['valor_empenhado'] - $row['valor_liquidado']), 2);
}

function liquidado_a_pagar(array $row): float {
    return (float) round(($row['valor_liquidado'] - $row['valor_pago']), 2);
}

function empenhado_a_pagar(array $row): float {
    return (float) round(($row['valor_empenhado'] - $row['valor_pago']), 2);
}

function saldo_inicial(array $row): float {
    switch ($row['conta_contabil'][0]){
        case '1':
        case '3':
        case '5':
        case '7':
            return (float) round(($row['saldo_inicial_devedor'] - $row['saldo_inicial_credor']), 2);
        case '2':
        case '4':
        case '6':
        case '8':
            return (float) round(($row['saldo_inicial_credor'] - $row['saldo_inicial_devedor']), 2);
    }
}

function saldo_atual(array $row): float {
    switch ($row['conta_contabil'][0]){
        case '1':
        case '3':
        case '5':
        case '7':
            return (float) round(($row['saldo_atual_devedor'] - $row['saldo_atual_credor']), 2);
        case '2':
        case '4':
        case '6':
        case '8':
            return (float) round(($row['saldo_atual_credor'] - $row['saldo_atual_devedor']), 2);
    }
}