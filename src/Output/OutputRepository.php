<?php

namespace PadConverter\Output;

use PadConverter\Output\Writer\Writer;
use PgSql\Connection;

/**
 * Repositório de dados de saída.
 *
 * @author Everton
 */
final class OutputRepository
{

    private ?Connection $conn = null;

    private readonly int $remessa;


    public function __construct(string $connectionString, int $remessa)
    {
        $con = pg_connect($connectionString);
        if (!$con) {
            $error = pg_last_error($this->con);
            trigger_error("Falha ao conectar com {$connectionString}: {$error}", E_USER_ERROR);
        }
        $this->conn = $con;
        $this->remessa = $remessa;
    }

    public function getWriterFor(string $tableName): Writer
    {
        return new Writer($this->conn, $tableName, $this->remessa);
    }

    public function __destruct()
    {
        pg_close($this->conn);
    }

    public function getConnection(): Connection
    {
        return $this->conn;
    }
}
