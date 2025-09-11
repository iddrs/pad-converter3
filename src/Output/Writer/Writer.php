<?php

namespace PadConverter\Output\Writer;

final class Writer
{
    private \PgSql\Connection $con;

    private readonly string $tableName;

    private int $remessa;

    public function __construct(\PgSql\Connection $con, string $tableName, int $remessa)
    {
        $this->con = $con;
        $this->tableName = "pad.{$tableName}";
        $this->remessa = $remessa;
        $this->init();
    }

    private function init(): void
    {
        if (!pg_query($this->con, 'BEGIN')) {
            $error = pg_last_error($this->con);
            trigger_error("Falha ao iniciar a transação para {$this->tableName}: {$error}", E_USER_ERROR);
        }
    }

    public function write(array $row): bool
    {
        if (!pg_insert($this->con, $this->tableName, $row)) {
            $error = pg_last_error($this->con);
            var_dump($row);
            trigger_error("Falha ao inserir dados em {$this->tableName}: {$error}", E_USER_ERROR);
            return false;
        } else {
            return true;
        }
    }

    public function save(): void
    {
        if (!pg_query($this->con, "DELETE FROM {$this->tableName} WHERE remessa = {$this->remessa}")) {
            $error = pg_last_error($this->con);
            trigger_error("Falha remover a remessa {$this->remessa} de {$this->tableName}: {$error}", E_USER_ERROR);
        }

        if (!pg_query($this->con, "UPDATE {$this->tableName} SET remessa = {$this->remessa} WHERE remessa = 0")) {
            $error = pg_last_error($this->con);
            trigger_error("Falha ao atualizar a remessa {$this->remessa} para {$this->tableName}: {$error}", E_USER_ERROR);
        }

        if (!pg_query($this->con, 'COMMIT')) {
            $error = pg_last_error($this->con);
            trigger_error("Falha ao confirmar a transação para {$this->tableName}: {$error}", E_USER_ERROR);
        }
    }
}
