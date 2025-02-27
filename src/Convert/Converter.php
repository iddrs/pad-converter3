<?php

namespace App\Convert;

/**
 * Controlador da conversão.
 *
 * @author Everton
 */
class Converter
{

    private \App\Layout\LayoutRepository $layoutRepo;

    private \App\Source\SourceRepository $sourceRepo;

    private \App\Output\OutputRepository $outputRepo;

    private readonly int $remessa;

    public function __construct(\App\Layout\LayoutRepository $layout, \App\Source\SourceRepository $source, \App\Output\OutputRepository $output, int $remessa)
    {
        $this->layoutRepo = $layout;
        $this->sourceRepo = $source;
        $this->outputRepo = $output;
        $this->remessa = $remessa;
    }

    public function run(): void
    {
        foreach ($this->layoutRepo->getLayoutNames() as $layoutName) {
            printf("Processando layout %s ..." . PHP_EOL, strtoupper($layoutName));

            $layout = $this->layoutRepo->getLayoutFor($layoutName);
            $colspec = $layout->getColumns();
            $sources = $this->sourceRepo->getSourcesFor($layoutName);
            $writer = $this->outputRepo->getWriterFor($layoutName);

            foreach ($sources as $source) {
                printf("\tProcessando arquivo %s..." . PHP_EOL, $source->filename);
                $progressBar = new \NickBeen\ProgressBar\ProgressBar(maxProgress: $source->totalRows);
                $progressBar->start();
                while (($buffer = $source->getRow())) {
                    $row = $this->parse($source, $buffer, $colspec);
                    $writer->write($row);
                    $progressBar->tick();
                }
                $progressBar->finish();
            }

            printf("\tSalvando dados de %s ..." . PHP_EOL, $layoutName);
            $writer->save();

            printf("\tFinalizado processamento do layout %s." . PHP_EOL, $layoutName);
        }

        printf("Processamento terminado da remessa %s" . PHP_EOL, $this->remessa);
    }

    private function parse(\App\Source\Source $source, string $buffer, array $colspec): array
    {
        $data = [];

        foreach ($colspec as $col) {
            switch ($col->origin) {
                case 'source':
                    $val = substr($buffer, $col->start - 1, $col->len);
                    if (!is_null($col->transformer)) {
                        $fn = $col->transformer;
                        $val = $fn($val);
                    }
                    break;
                case 'header':
                    $prop = $col->prop;
                    $val = $source->$prop;
                    break;
                case 'calc':
                    $fn = $col->fn;
                    $val = $fn($data);
                    break;
            }
            $val = $this->typefy($col->type, $val);
            $data[$col->id] = $val;
        }
        $data = mb_convert_encoding($data, 'utf-8');
        return $data;
    }

    /**
     * 
     * @param string $type
     * @param type $val
     * @return type
     * @todo
     */
    private function typefy(string $type, mixed $val): mixed
    {
        switch ($type) {
            case 'string':
                return (string) $val;
            case 'int':
                return (int) $val;
            case 'date':
                if (is_null($val))
                    return null;
                return $val->format('Y-m-d');
            case 'currency':
                round($val, 2);
            case 'char':
                return (string) $val;
        }
    }

}
