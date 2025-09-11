<?php

namespace PadConverter\Source;

final class SourceRepository
{
    private array $filesToProcess = [];
    public int $totalFilesToProcess = 0;
    public int $totalLinesToProcess = 0;
    public int $remessa = 0;
    public function __construct(
        public readonly string $sourceDir
    ) {

    }

    public function readSourceMetadata(): self
    {
        foreach (glob($this->sourceDir . '*/*.txt', GLOB_MARK) as $item) {
            $filepath = realpath($item);
            $this->filesToProcess[] = $filepath;
            $this->totalFilesToProcess++;
            $this->parseMetadataFoFile($filepath);
        }

        return $this;
    }

    private function parseMetadataFoFile(string $fielpath): void
    {
        $file = fopen($fielpath, 'r');

        $this->getRemessaFromFileHeader(fgets($file), $fielpath);

        while (feof($file) === false) {
            $buffer = fgets($file);
            if (strtoupper(substr($buffer, 0, 11)) === 'FINALIZADOR') {
                fclose($file);
                return;
            }
            $this->totalLinesToProcess++;
        }

        fclose($file);
        throw new \DomainException("Linha finalizadora não encontrada no arquivo $fielpath");
    }

    private function getRemessaFromFileHeader(string $header, string $filepath): void
    {
        $remessa = intval(substr($header, 26, 4) . substr($header, 24, 2));
        if ($this->remessa === 0) {
            $this->remessa = $remessa;
            return;
        }
        if ($this->remessa !== $remessa) {
            throw new \DomainException(sprintf('%s tem remessa %s diferente da remessa detectada %s', $filepath, $remessa, $this->remessa));
        }
        return;
    }

    public function getSourcesFor(string $layoutName): array
    {
        $files = [];
        foreach ($this->filesToProcess as $filename) {
            if (strtolower($layoutName) === strtolower(basename($filename, '.txt'))) {
                if (file_exists($filename))
                    $files[] = new Source($filename);
            }
        }
        return $files;
    }
}