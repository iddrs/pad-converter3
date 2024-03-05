<?php

//$remessa = 202312;//para teste
//$remessa = 202401;//para teste

/**
 * Monta uma tabela de restos a pagar com base em empenho, liquida, pagament.
 */

//require 'vendor/autoload.php';

function monta_restos_pagar(int $remessa, PgSql\Connection $con) {
    // Prepara variáveis globais
    $data_inicial= date_create_from_format('Ymd', substr($remessa, 0, 4).'0101');
    $data_final = date_create_from_format('Ymd', $remessa.'01');
    $data_final->modify('last day of this month');

    // Conecta ao banco de dados.
//    $connectionString = 'host=localhost port=5432 dbname=pmidd user=postgres password=lise890';
//    $con = pg_connect($connectionString);
//    if(!$con) {
//        $error = pg_last_error($con);
//        trigger_error ("Falha ao conectar com {$connectionString}: {$error}", E_USER_ERROR);
//    }
//    printf('Conectado a %s'.PHP_EOL, $connectionString);

    // Seleciona os empenhos.
    $sql = sprintf('SELECT DISTINCT
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
                    FROM PAD.EMPENHO WHERE REMESSA = %d AND ANO_EMPENHO < %d', $remessa, $data_inicial->format('Y'));

    $empenhos = pg_query($con, $sql);
    printf('Encontrados %d empenhos.'.PHP_EOL, pg_num_rows($empenhos));

    $soma = [
        'saldo_nao_processado_inscritos_exercicios_anteriores' => 0.0,
        'nao_processado_inscritos_ultimo_exercicio' => 0.0,
        'saldo_inicial_nao_processado' => 0.0,
        'saldo_processado_inscritos_exercicios_anteriores' => 0.0,
        'processado_inscritos_ultimo_exercicio' => 0.0,
        'saldo_inicial_nao_processado' => 0.0,
        'saldo_inicial_processado' => 0.0,
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
    ];//para teste

    $rp = pg_fetch_all($empenhos, PGSQL_ASSOC);

    // Calcula nao processados inscritos em exercícios anteriores
    foreach ($rp as $i => $row){
        $row['remessa'] = 0;// Necessário para incluir a remesssa sem excluir os dados antigos
        $sql = sprintf('SELECT SUM(VALOR_EMPENHO) AS EMPENHADO
                        FROM PAD.EMPENHO
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_EMPENHO < \'%s\' AND ANO_EMPENHO < %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $empenhado = money_to_float(pg_fetch_all($result)[0]['empenhado']);

        // valor liquidado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_LIQUIDACAO < \'%s\' AND ANO_EMPENHO < %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        $valor = $empenhado - $liquidado;
        $rp[$i] = array_merge($rp[$i], $row, ['saldo_nao_processado_inscritos_exercicios_anteriores' => $valor]);
        $soma['saldo_nao_processado_inscritos_exercicios_anteriores'] += $valor;
    }

    // Calcula nao processados inscritos no exercicio anterior
    foreach ($rp as $i => $row){
        $sql = sprintf('SELECT SUM(VALOR_EMPENHO) AS EMPENHADO
                        FROM PAD.EMPENHO
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_EMPENHO < \'%s\' AND ANO_EMPENHO = %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $empenhado = money_to_float(pg_fetch_all($result)[0]['empenhado']);

        // valor liquidado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_LIQUIDACAO < \'%s\' AND ANO_EMPENHO = %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        $valor = $empenhado - $liquidado;
        $rp[$i] = array_merge($rp[$i], $row, ['nao_processado_inscritos_ultimo_exercicio' => $valor]);
        $soma['nao_processado_inscritos_ultimo_exercicio'] += $valor;
    }

    // Calcula o saldo inicial de restos não processados
    foreach($rp as $i => $row){
        // valor empenhado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_EMPENHO) AS EMPENHADO
                        FROM PAD.EMPENHO
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_EMPENHO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $empenhado = money_to_float(pg_fetch_all($result)[0]['empenhado']);

        // valor liquidado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_LIQUIDACAO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        // saldo rpnp
        $saldo_rpnp = $empenhado - $liquidado;
        $rp[$i] = array_merge($rp[$i], $row, ['saldo_inicial_nao_processado' => $saldo_rpnp]);
        $soma['saldo_inicial_nao_processado'] += $saldo_rpnp;
    }

    // Calcula processados inscritos em exercícios anteriores
    foreach ($rp as $i => $row){
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_PAGAMENTO < \'%s\' AND ANO_EMPENHO < %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);

        // valor liquidado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_LIQUIDACAO < \'%s\' AND ANO_EMPENHO < %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        $valor = $liquidado - $pago;
        $rp[$i] = array_merge($rp[$i], $row, ['saldo_processado_inscritos_exercicios_anteriores' => $valor]);
        $soma['saldo_processado_inscritos_exercicios_anteriores'] += $valor;
    }

    // Calcula processados inscritos no exercicio anterior
    foreach ($rp as $i => $row){
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_PAGAMENTO < \'%s\' AND ANO_EMPENHO = %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);

        // valor liquidado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_LIQUIDACAO < \'%s\' AND ANO_EMPENHO = %d', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_inicial->format('Y') - 1);
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        $valor = $liquidado - $pago;
        $rp[$i] = array_merge($rp[$i], $row, ['processado_inscritos_ultimo_exercicio' => $valor]);
        $soma['processado_inscritos_ultimo_exercicio'] += $valor;
    }

    // Calcula o saldo inicial de restos processados
    foreach ($rp as $i => $row){
        // valor pago antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_PAGAMENTO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);

        // valor liquidado antes da data inicial
        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_LIQUIDACAO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        // saldo rpp
        $saldo_rpp = $liquidado - $pago;
        $rp[$i] = array_merge($rp[$i], $row, ['saldo_inicial_processado' => $saldo_rpp]);
        $soma['saldo_inicial_processado'] += $saldo_rpp;
    }

    // Calcula as liquidações no período
    foreach ($rp as $i => $row){
        if($row['saldo_inicial_nao_processado'] > 0.0){
            $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                            FROM PAD.LIQUIDAC
                            WHERE REMESSA = %d
                                    --AND VALOR_LIQUIDACAO > \'0.00\'::money
                                    AND CHAVE_EMPENHO LIKE \'%s\'
                                    AND DATA_LIQUIDACAO BETWEEN \'%s\' AND \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));

            $result = pg_query($con, $sql);
            $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);
        }else{
            $liquidado = 0.0;
        }

        $rp[$i] = array_merge($rp[$i], $row, ['rp_liquidado' => $liquidado]);
        $soma['rp_liquidado'] += $liquidado;
    }

    // Calcula os pagamentos de restos não processados no período
    foreach ($rp as $i => $row){
        if($row['saldo_inicial_nao_processado'] > 0.0){
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_PAGAMENTO BETWEEN \'%s\' AND \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));

        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);
        } else {
            $pago = 0.0;
        }
        $rp[$i] = array_merge($rp[$i], $row, ['nao_processado_pago' => $pago]);
        $soma['nao_processado_pago'] += $pago;
    }

    // Calcula os pagamentos de restos processados no período
    foreach ($rp as $i => $row){
        if($row['saldo_inicial_processado'] > 0.0){
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_PAGAMENTO BETWEEN \'%s\' AND \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));

        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);
        } else {
            $pago = 0.0;
        }
        $rp[$i] = array_merge($rp[$i], $row, ['processado_pago' => $pago]);
        $soma['processado_pago'] += $pago;
    }

    // Calcula os pagamentos de restos no período
    foreach ($rp as $i => $row){
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                AND DATA_PAGAMENTO BETWEEN \'%s\' AND \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));

        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);
        $rp[$i] = array_merge($rp[$i], $row, ['rp_pago' => $pago]);
        $soma['rp_pago'] += $pago;
    }

    // Calcula os cancelamentos de restos não processados
    foreach ($rp as $i => $row){
        if($row['saldo_inicial_nao_processado'] > 0.0){
            $sql = sprintf('SELECT SUM(VALOR_EMPENHO) AS EMPENHADO
                            FROM PAD.EMPENHO
                            WHERE REMESSA = %d
                                    AND VALOR_EMPENHO< \'0.00\'::money
                                    AND CHAVE_EMPENHO LIKE \'%s\'
                                    AND DATA_EMPENHO BETWEEN \'%s\' AND \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));

            $result = pg_query($con, $sql);
            $empenhado = money_to_float(pg_fetch_all($result)[0]['empenhado']) * -1;
        }else{
            $empenhado = 0.0;
        }

        $rp[$i] = array_merge($rp[$i], $row, ['nao_processado_cancelado' => $empenhado]);
        $soma['nao_processado_cancelado'] += $empenhado;
    }

    // Calcula os cancelamentos de restos processados
    foreach ($rp as $i => $row){
        if($row['saldo_inicial_processado'] > 0.0){
            $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                            FROM PAD.LIQUIDAC
                            WHERE REMESSA = %d
                                    AND VALOR_LIQUIDACAO < \'0.00\'::money
                                    AND CHAVE_EMPENHO LIKE \'%s\'
                                    AND DATA_LIQUIDACAO BETWEEN \'%s\' AND \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'), $data_final->format('Y-m-d'));

            $result = pg_query($con, $sql);
            $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']) * -1;
        }else{
            $liquidado = 0.0;
        }

        $rp[$i] = array_merge($rp[$i], $row, ['processado_cancelado' => $liquidado]);
        $soma['processado_cancelado'] += $liquidado;
    }

    // Calcula o saldo final não processado
    foreach($rp as $i => $row){
        $sql = sprintf('SELECT SUM(VALOR_EMPENHO) AS EMPENHADO
                        FROM PAD.EMPENHO
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                --AND DATA_EMPENHO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $empenhado = money_to_float(pg_fetch_all($result)[0]['empenhado']);

        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                --AND DATA_LIQUIDACAO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        // saldo rpnp
        $saldo_rpnp = $empenhado - $liquidado;
        $rp[$i] = array_merge($rp[$i], $row, ['saldo_final_nao_processado' => $saldo_rpnp]);
        $soma['saldo_final_nao_processado'] += $saldo_rpnp;
    }

    // Calcula o saldo final de restos processados
    foreach ($rp as $i => $row){
        $sql = sprintf('SELECT SUM(VALOR_PAGAMENTO) AS PAGO
                        FROM PAD.PAGAMENT
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                --AND DATA_PAGAMENTO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $pago = money_to_float(pg_fetch_all($result)[0]['pago']);

        $sql = sprintf('SELECT SUM(VALOR_LIQUIDACAO) AS LIQUIDADO
                        FROM PAD.LIQUIDAC
                        WHERE REMESSA = %d
                                AND CHAVE_EMPENHO LIKE \'%s\'
                                --AND DATA_LIQUIDACAO < \'%s\'', $remessa, $row['chave_empenho'], $data_inicial->format('Y-m-d'));
        $result = pg_query($con, $sql);
        $liquidado = money_to_float(pg_fetch_all($result)[0]['liquidado']);

        // saldo rpp
        $saldo_rpp = $liquidado - $pago;
        $rp[$i] = array_merge($rp[$i], $row, ['saldo_final_processado' => $saldo_rpp]);
        $soma['saldo_final_processado'] += $saldo_rpp;
    }

    // Calcula outros valores
    foreach ($rp as $i => $row){

        $rp_cancelado = round($row['nao_processado_cancelado'] + $row['processado_cancelado'], 2);
        $rp[$i] = array_merge($rp[$i], $row, ['rp_cancelado' => $rp_cancelado]);
        $soma['rp_cancelado'] += $rp_cancelado;

        $rp_saldo_inicial = round($row['saldo_inicial_nao_processado'] + $row['saldo_inicial_processado'], 2);
        $rp[$i] = array_merge($rp[$i], $row, ['rp_saldo_inicial' => $rp_saldo_inicial]);
        $soma['rp_saldo_inicial'] += $rp_saldo_inicial;

        $rp_saldo_final = round($row['saldo_final_nao_processado'] + $row['saldo_final_processado'], 2);
        $rp[$i] = array_merge($rp[$i], $row, ['rp_saldo_final' => $rp_saldo_final]);
        $soma['rp_saldo_final'] += $rp_saldo_final;
    }

    // Salva no banco de dados
    if(!pg_query($con, 'BEGIN')) {
        $error = pg_last_error($con);
        trigger_error("Falha ao iniciar a transação para restos_pagar: {$error}", E_USER_ERROR);
    }

    foreach ($rp as $row){
        if(!pg_insert($con, 'pad.restos_pagar', $row)){
            $error = pg_last_error($con);
            var_dump($row);
            trigger_error("Falha ao inserir dados em restos_pagar: {$error}", E_USER_ERROR);
        }
    }

    if(!pg_query($con, "DELETE FROM pad.restos_pagar WHERE remessa = $remessa")) {
        $error = pg_last_error($con);
        trigger_error("Falha remover a remessa $remessa de restos_pagar: {$error}", E_USER_ERROR);
    }

    if(!pg_query($con, "UPDATE pad.restos_pagar SET remessa = $remessa WHERE remessa = 0")) {
        $error = pg_last_error($con);
        trigger_error("Falha ao atualizar a remessa $remessa para restos_pagar: {$error}", E_USER_ERROR);
    }

    if(!pg_query($con, 'COMMIT')) {
        $error = pg_last_error($con);
        trigger_error("Falha ao confirmar a transação para restos_pagar: {$error}", E_USER_ERROR);
    }

    echo PHP_EOL;
    print_r($soma);
}