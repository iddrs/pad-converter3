<?php

namespace PadConverter\Convert;

final class Converter
{

    private \PadConverter\Layout\LayoutRepository $layoutRepo;

    private \PadConverter\Source\SourceRepository $sourceRepo;

    private \PadConverter\Output\OutputRepository $outputRepo;

    private readonly int $remessa;

    private int $totalLinesProcessed = 0;

    public function __construct(\PadConverter\Layout\LayoutRepository $layout, \PadConverter\Source\SourceRepository $source, \PadConverter\Output\OutputRepository $output, int $remessa)
    {
        $this->layoutRepo = $layout;
        $this->sourceRepo = $source;
        $this->outputRepo = $output;
        $this->remessa = $remessa;
    }

    public function run(): void
    {
        foreach ($this->layoutRepo->getLayoutNames() as $layoutName) {
            print_info("Processando layout:", strtoupper($layoutName));

            $layout = $this->layoutRepo->getLayoutFor($layoutName);
            $colspec = $layout->getColumns();
            $sources = $this->sourceRepo->getSourcesFor($layoutName);
            $writer = $this->outputRepo->getWriterFor($layoutName);

            foreach ($sources as $source) {
                printnl($source->filename);
                while (($buffer = $source->getRow())) {
                    $row = $this->parse($source, $buffer, $colspec);
                    $this->totalLinesProcessed++;
                    $writer->write($row);
                }
            }

            printnl("Salvando dados...");
            $writer->save();
        }

    }

    private function parse(\PadConverter\Source\Source $source, string $buffer, array $colspec): array
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
