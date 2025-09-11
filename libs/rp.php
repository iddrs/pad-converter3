<?php

/**
 * Monta uma tabela de restos a pagar com base em empenho, liquidac, pagament.
 */

function monta_restos_pagar(int $remessa, \PgSql\Connection $con)
{
    // Prepara variáveis globais
    $data_inicial = date_create_from_format('Ymd', substr($remessa, 0, 4) . '0101');
    $data_final = date_create_from_format('Ymd', $remessa . '01');
    $data_final->modify('last day of this month');

    // Pré-seleciona os empenhos.
    $sql = sprintf("SELECT distinct
                            REMESSA,
                            ORGAO,
                            UNIORCAM,
                            FUNCAO,
                            SUBFUNCAO,
                            PROGRAMA,
                            PROJATIV,
                            RUBRICA,
                            RECURSO_VINCULADO,
                            CONTRAPARTIDA_RECURSO_VINCULADO,
                            CHAVE_EMPENHO,
                            ANO_EMPENHO,
                            ENTIDADE_EMPENHO,
                            NR_EMPENHO,
                            CREDOR,
                            CARACTERISTICA_PECULIAR_DESPESA,
                            COMPLEMENTO_RECURSO_VINCULADO,
                            INDICADOR_EXERCICIO_FONTE_RECURSO,
                            FONTE_RECURSO,
                            CODIGO_ACOMPANHAMENTO_ORCAMENTARIO,
                            ENTIDADE
                    FROM PAD.EMPENHO WHERE REMESSA = %d AND ANO_EMPENHO < %d
                    ", $remessa, $data_inicial->format('Y'), $data_inicial->format('Y-m-d'));
    //    echo $sql;exit();
    $empenhos = pg_query($con, $sql);
    print_info('Empenhos de exercícios anteriores', pg_num_rows($empenhos));

    $soma = [
        'empenhado' => 0.0,
        'liquidado' => 0.0,
        'pago' => 0.0,
        'saldo_nao_processado_inscritos_exercicios_anteriores' => 0.0,
        'nao_processado_inscritos_ultimo_exercicio' => 0.0,
        'saldo_inicial_nao_processado' => 0.0,
        'saldo_inicial_processado' => 0.0,
        'saldo_processado_inscritos_exercicios_anteriores' => 0.0,
        'processado_inscritos_ultimo_exercicio' => 0.0,
        'rp_saldo_inicial' => 0.0,
        'rp_liquidado' => 0.0,
        'nao_processado_cancelado' => 0.0,
        'processado_cancelado' => 0.0,
        'rp_cancelado' => 0.0,
        'nao_processado_pago' => 0.0,
        'processado_pago' => 0.0,
        'rp_pago' => 0.0,
        'saldo_final_nao_processado' => 0.0,
        'saldo_final_processado' => 0.0,
        'rp_saldo_final' => 0.0
    ];

    // Identifica empenhos com saldo a pagar
    $rp = [];
    foreach (pg_fetch_all($empenhos, PGSQL_ASSOC) as $item) {
        $chave = $item['chave_empenho'];

        $sql = sprintf("
            SELECT
                SUM(valor_empenho)::decimal AS empenhado
            FROM pad.empenho
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_empenho < '%s'
            ", $remessa, $chave, $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $empenhado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['empenhado'];

        $sql = sprintf("
            SELECT
                SUM(valor_pagamento)::decimal AS pago
            FROM pad.pagament
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_pagamento < '%s'
            ", $remessa, $chave, $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $pago = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['pago'];

        $rp_saldo_inicial = (float) round($empenhado - $pago, 2);
        if ($rp_saldo_inicial > 0) {
            $item = array_merge($item, $soma);
            $item['rp_saldo_inicial'] = $rp_saldo_inicial;
            $item['pago'] = $pago;
            $item['empenhado'] = $empenhado;
            $rp[$chave] = $item;
            $rp[$chave]['remessa'] = 0;
        } else {
            printf("%s : %s\t\t\t\t\t\t%s\t\t\t\t\t\t%s" . PHP_EOL, $item['chave_empenho'], number_format($item['empenhado'], 2, ',', '.'), number_format($pago, 2, ',', '.'), number_format($rp_saldo_inicial, 2, ',', '.'));
        }
    }
    print_info('Empenhos de restos a pagar', count($rp));
    nl();
    print_info('Saldo Inicial Total:', number_format(array_sum(array_column($rp, 'rp_saldo_inicial')), 2, ',', '.'));

    // Calcula o saldo inicial não processado
    foreach ($rp as $chave => $item) {
        $empenhado = $item['empenhado'];

        $sql = sprintf("
            SELECT
                SUM(valor_liquidacao)::decimal AS liquidado
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao < '%s'
            ", $remessa, $chave, $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['liquidado'];

        $saldo_inicial_nao_processado = (float) round($empenhado - $liquidado, 2);
        $rp[$chave]['saldo_inicial_nao_processado'] = $saldo_inicial_nao_processado;
        $rp[$chave]['liquidado'] = $liquidado;
    }
    print_info('Saldo Inicial Não Processado: ', number_format(array_sum(array_column($rp, 'saldo_inicial_nao_processado')), 2, ',', '.'));

    // Calcula o saldo inicial não processado inscritos em exercícios anteriores
    foreach ($rp as $chave => $item) {
        $data_inicial_anterior = date_create_from_format('Ymd', ($data_inicial->format('Y') - 1) . '0101');
        $sql = sprintf("
            SELECT
                SUM(valor_empenho)::decimal AS empenhado
            FROM pad.empenho
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_empenho < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $empenhado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['empenhado'];

        $sql = sprintf("
            SELECT
                SUM(valor_liquidacao)::decimal AS liquidado
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['liquidado'];

        $saldo_nao_processado_inscritos_exercicios_anteriores = (float) round($empenhado - $liquidado, 2);
        $rp[$chave]['saldo_nao_processado_inscritos_exercicios_anteriores'] = $saldo_nao_processado_inscritos_exercicios_anteriores;
    }
    print_info('Saldo Não Processado inscritos em exercícios anteriores: ', number_format(array_sum(array_column($rp, 'saldo_nao_processado_inscritos_exercicios_anteriores')), 2, ',', '.'));

    // Calcula o saldo inicial não processado inscritos no último exercício
    foreach ($rp as $chave => $item) {
        $data_inicial_anterior = date_create_from_format('Ymd', ($data_inicial->format('Y') - 1) . '0101');
        $sql = sprintf("
            SELECT
                SUM(valor_empenho)::decimal AS empenhado
            FROM pad.empenho
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_empenho >= '%s' and data_empenho < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'), $data_inicial->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $empenhado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['empenhado'];

        $sql = sprintf("
            SELECT
                SUM(valor_liquidacao)::decimal AS liquidado
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao >= '%s' and data_liquidacao < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'), $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['liquidado'];

        $nao_processado_inscritos_ultimo_exercicio = (float) round($empenhado - $liquidado, 2);
        $rp[$chave]['nao_processado_inscritos_ultimo_exercicio'] = $nao_processado_inscritos_ultimo_exercicio;
    }
    print_info('Saldo Não Processado inscritos no último exercício: ', number_format(array_sum(array_column($rp, 'nao_processado_inscritos_ultimo_exercicio')), 2, ',', '.'));

    // Calcula o saldo inicial não processado
    foreach ($rp as $chave => $item) {
        $liquidado = $item['liquidado'];
        $pago = $item['pago'];
        $saldo_inicial_processado = (float) round($liquidado - $pago, 2);
        $rp[$chave]['saldo_inicial_processado'] = $saldo_inicial_processado;
    }
    print_info('Saldo Inicial Processado: ', number_format(array_sum(array_column($rp, 'saldo_inicial_processado')), 2, ',', '.'));

    // Calcula o saldo inicial processado inscritos em exercícios anteriores
    foreach ($rp as $chave => $item) {
        $data_inicial_anterior = date_create_from_format('Ymd', ($data_inicial->format('Y') - 1) . '0101');
        $sql = sprintf("
            SELECT
                SUM(valor_liquidacao)::decimal AS liquidado
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $liquidado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['liquidado'];

        $sql = sprintf("
            SELECT
                SUM(valor_pagamento)::decimal AS pago
            FROM pad.pagament
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_pagamento < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $pago = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['pago'];

        $saldo_processado_inscritos_exercicios_anteriores = (float) round($liquidado - $pago, 2);
        $rp[$chave]['saldo_processado_inscritos_exercicios_anteriores'] = $saldo_processado_inscritos_exercicios_anteriores;
    }
    print_info('Saldo Processado inscritos em exercícios anteriores: ', number_format(array_sum(array_column($rp, 'saldo_processado_inscritos_exercicios_anteriores')), 2, ',', '.'));

    // Calcula o saldo inicial processado inscritos no último exercício
    foreach ($rp as $chave => $item) {
        $data_inicial_anterior = date_create_from_format('Ymd', ($data_inicial->format('Y') - 1) . '0101');
        $sql = sprintf("
            SELECT
                SUM(valor_liquidacao)::decimal AS liquidado
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao >= '%s' and data_liquidacao < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'), $data_inicial->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $liquidado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['liquidado'];

        $sql = sprintf("
            SELECT
                SUM(valor_pagamento)::decimal AS pago
            FROM pad.pagament
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_pagamento >= '%s' and data_pagamento < '%s'
            ", $remessa, $chave, $data_inicial_anterior->format('Y-m-d'), $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $pago = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['pago'];

        $processado_inscritos_ultimo_exercicio = (float) round($liquidado - $pago, 2);
        $rp[$chave]['processado_inscritos_ultimo_exercicio'] = $processado_inscritos_ultimo_exercicio;
    }
    print_info('Saldo Processado inscritos no último exercício: ', number_format(array_sum(array_column($rp, 'processado_inscritos_ultimo_exercicio')), 2, ',', '.'));

    // Calcula o RPNP liquidado
    foreach ($rp as $chave => $item) {
        $sql = sprintf("
            SELECT
                SUM(valor_liquidacao)::decimal AS liquidado
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao between '%s' and '%s'
                AND valor_liquidacao::decimal > 0.0
            ", $remessa, $chave, $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $liquidado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['liquidado'];

        $rp_liquidado = $liquidado;
        $rp[$chave]['rp_liquidado'] = $rp_liquidado;
    }
    print_info('RP Não Processado Liquidado: ', number_format(array_sum(array_column($rp, 'rp_liquidado')), 2, ',', '.'));

    // Calcula o RP Processado Cancelado
    foreach ($rp as $chave => $item) {
        // Busca todos os cancelamentos
        $sql = sprintf("
            SELECT
                nr_liquidacao, valor_liquidacao::decimal
            FROM pad.liquidac
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_liquidacao between '%s' and '%s'
                AND valor_liquidacao::decimal < 0.0
            ", $remessa, $chave, $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $cancelamentos = pg_fetch_all($result, PGSQL_ASSOC);

        foreach ($cancelamentos as $item) {
            // Verifica se para chave_empenho+nr_liquidacao existe liquidação no ano
            $sql1 = sprintf("
                select count(remessa) as cancelamento
                from pad.liquidac
                where remessa = %d
                and chave_empenho = '%s'
                and nr_liquidacao = %d
                AND data_liquidacao between '%s' and '%s'
                AND valor_liquidacao::decimal > 0.0
            ", $remessa, $chave, $item['nr_liquidacao'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));
            $result1 = pg_query($con, $sql1);
            if (pg_num_rows($result) !== 0) {//se tem liquidações no ano, é cancelamento de processados
                $rp[$chave]['processado_cancelado'] += (float) round($item['valor_liquidacao'] * -1, 2);
            } else {// senão, é cancelamento de não processado
//                $rp[$chave]['nao_processado_cancelado'] += (float) round($item['valor_liquidacao']*-1, 2);
            }
            //            $rp[$chave]['rp_cancelado'] += (float) round($item['valor_liquidacao']*-1, 2);
        }
    }
    print_info('RP Processado Cancelado: ', number_format(array_sum(array_column($rp, 'processado_cancelado')), 2, ',', '.'));

    // Calcula o RP Não Processado Cancelado e Total Cancelado
    foreach ($rp as $chave => $item) {
        $sql = sprintf("
            SELECT
                SUM(valor_empenho*-1)::decimal AS empenhado
            FROM pad.empenho
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_empenho between '%s' and '%s'
                AND valor_empenho::decimal < 0.0
            ", $remessa, $chave, $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $empenhado = (float) pg_fetch_row($result, 0, PGSQL_ASSOC)['empenhado'];

        $nao_processado_cancelado = $empenhado;
        $rp[$chave]['nao_processado_cancelado'] = $nao_processado_cancelado - $rp[$chave]['processado_cancelado'];
        $rp[$chave]['rp_cancelado'] = $rp[$chave]['nao_processado_cancelado'] + $rp[$chave]['processado_cancelado'];
    }
    print_info('RP Não Processado Cancelado: ', number_format(array_sum(array_column($rp, 'nao_processado_cancelado')), 2, ',', '.'));
    print_info('RP Total Cancelado: ', number_format(array_sum(array_column($rp, 'rp_cancelado')), 2, ',', '.'));

    // Calcula o RP Processado Pago
    foreach ($rp as $chave => $item) {
        // Busca todos os pagamentos
        $sql = sprintf("
            SELECT
                data_pagamento, valor_pagamento::decimal
            FROM pad.pagament
            WHERE remessa = %d
                AND chave_empenho = '%s'
                AND data_pagamento between '%s' and '%s'
            ", $remessa, $chave, $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));
        //        echo $sql;exit();
        $result = pg_query($con, $sql);
        $pagamentos = pg_fetch_all($result, PGSQL_ASSOC);
        foreach ($pagamentos as $subitem) {
            // Verifica se para chave_empenho+nr_liquidacao existe liquidação no ano
            $sql1 = sprintf("
                select *
                from pad.liquidac
                where remessa = %d
                and chave_empenho = '%s'
                AND data_liquidacao between '%s' and '%s'
                AND valor_liquidacao::decimal = %s
            ", $remessa, $chave, $data_inicial->format('Y-m-d'), $subitem['data_pagamento'], $subitem['valor_pagamento']);
            //            echo $sql1, PHP_EOL;
            $result1 = pg_query($con, $sql1);
            //            echo $chave, ' : ', $subitem['nr_liquidacao'], ' : ', pg_fetch_row($result1, 0, PGSQL_ASSOC)['liquidacao'], PHP_EOL;
            if (pg_num_rows($result1) === 0) {//se tem liquidações no ano, é pagamento de processados
                $rp[$chave]['processado_pago'] += (float) round($subitem['valor_pagamento'], 2);
            } else {// senão, é pagamento de não processado
                $rp[$chave]['nao_processado_pago'] += (float) round($subitem['valor_pagamento'], 2);
            }
            $rp[$chave]['rp_pago'] += (float) round($subitem['valor_pagamento'], 2);
        }
    }
    print_info('RP Processado Pago: ', number_format(array_sum(array_column($rp, 'processado_pago')), 2, ',', '.'));
    print_info('RP Não Processado Pago: ', number_format(array_sum(array_column($rp, 'nao_processado_pago')), 2, ',', '.'));
    print_info('RP Total Pago: ', number_format(array_sum(array_column($rp, 'rp_pago')), 2, ',', '.'));

    // Calcula os saldos finais
    foreach ($rp as $chave => $item) {
        $rp[$chave]['saldo_final_nao_processado'] = (float) round($item['saldo_inicial_nao_processado'] - $item['nao_processado_cancelado'] - $item['nao_processado_pago'] - ($item['rp_liquidado'] - $item['nao_processado_pago']), 2);
        $rp[$chave]['saldo_final_processado'] = (float) round($item['saldo_inicial_processado'] - $item['processado_cancelado'] - $item['processado_pago'] + ($item['rp_liquidado'] - $item['nao_processado_pago']), 2);
        $rp[$chave]['rp_saldo_final'] = (float) round($rp[$chave]['saldo_final_nao_processado'] + $rp[$chave]['saldo_final_processado'], 2);
    }
    print_info('Saldo Final Não Processado: ', number_format(array_sum(array_column($rp, 'saldo_final_nao_processado')), 2, ',', '.'));
    print_info('Saldo Final Processado: ', number_format(array_sum(array_column($rp, 'saldo_final_processado')), 2, ',', '.'));
    print_info('Saldo Final Total: ', number_format(array_sum(array_column($rp, 'rp_saldo_final')), 2, ',', '.'));


    //    print_r($rp);
//    exit();

    // Salva no banco de dados
    $rp = array_values($rp);
    print_info('Salvando registros no banco de dados...', count($rp));
    if (!pg_query($con, 'BEGIN')) {
        $error = pg_last_error($con);
        trigger_error("Falha ao iniciar a transação para restos_pagar: {$error}", E_USER_ERROR);
    }

    //    $rp = array_values($rp);//Reseta as chaves do array para poder usar pg_insert
    foreach ($rp as $row) {
        if (!pg_insert($con, 'pad.restos_pagar', $row)) {
            $error = pg_last_error($con);
            var_dump($row);
            trigger_error("Falha ao inserir dados em restos_pagar: {$error}", E_USER_ERROR);
        }
    }

    if (!pg_query($con, "DELETE FROM pad.restos_pagar WHERE remessa = $remessa")) {
        $error = pg_last_error($con);
        trigger_error("Falha remover a remessa $remessa de restos_pagar: {$error}", E_USER_ERROR);
    }

    if (!pg_query($con, "UPDATE pad.restos_pagar SET remessa = $remessa WHERE remessa = 0")) {
        $error = pg_last_error($con);
        trigger_error("Falha ao atualizar a remessa $remessa para restos_pagar: {$error}", E_USER_ERROR);
    }

    if (!pg_query($con, 'COMMIT')) {
        $error = pg_last_error($con);
        trigger_error("Falha ao confirmar a transação para restos_pagar: {$error}", E_USER_ERROR);
    }

    nl();
}