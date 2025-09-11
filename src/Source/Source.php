<?php

namespace PadConverter\Source;

final class Source
{

    private $fhandler;

    public readonly string $filename;

    public readonly string $cnpj;

    public readonly \DateTime $dataInicial;

    public readonly \DateTime $dataFinal;

    public readonly \DateTime $dataGeracao;

    public readonly int $totalRows;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->open();
        $this->loadHeaderData();
        $this->loadTotalRows();
        rewind($this->fhandler); // Retrocede o ponteiro para a primeira linha
        fgets($this->fhandler); // Pula a primeira linha
    }

    private function open(): void
    {
        $this->fhandler = fopen($this->filename, 'r');
        if (!$this->fhandler) {
            trigger_error("Falha ao abrir o arquivo [{$this->filename}]", E_USER_ERROR);
        }
    }

    private function loadHeaderData(): void
    {
        $header = trim(fgets($this->fhandler));
        $this->extractCnpjFromHeader($header);
        $this->extractDataInicialFromHeader($header);
        $this->extractDataFinalFromHeader($header);
        $this->extractDataGeracaoFromHeader($header);
    }

    private function extractCnpjFromHeader(string $header): void
    {
        $this->cnpj = (string) substr($header, 0, 14);
    }

    private function extractDataInicialFromHeader(string $header): void
    {
        $this->dataInicial = \DateTime::createFromFormat('dmY', (string) substr($header, 14, 8));
    }

    private function extractDataFinalFromHeader(string $header): void
    {
        $this->dataFinal = \DateTime::createFromFormat('dmY', (string) substr($header, 22, 8));
    }

    private function extractDataGeracaoFromHeader(string $header): void
    {
        $this->dataGeracao = \DateTime::createFromFormat('dmY', (string) substr($header, 30, 8));
    }

    private function loadTotalRows(): void
    {
        $file = file($this->filename);
        $last_line = $file[array_key_last($file)];
        unset($file);
        $this->totalRows = (int) str_replace('FINALIZADOR', '', $last_line);
    }

    /**
     * Lê uma linha de dados.
     * 
     * @return string
     */
    public function getRow(): string|bool
    {
        $buffer = trim(fgets($this->fhandler));
        if (substr($buffer, 0, 11) === 'FINALIZADOR')
            return false;
        return $buffer;
    }
}
