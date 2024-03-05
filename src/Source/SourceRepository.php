<?php

namespace App\Source;

/**
 * Repositório dos dados dos txt do PAD.
 *
 * @author Everton
 */
class SourceRepository {
    public readonly array $sourceDirs;
    
    public function __construct(string ...$sourceDir) {
        $this->sourceDirs = $sourceDir;
    }
    
    /**
     * Lista de arquivos com os dados.
     * 
     * @param string $layoutName
     * @return array
     */
    public function getSourcesFor(string $layoutName): array {
        $files = [];
        foreach ($this->sourceDirs as $dir){
            $filename = "{$dir}{$layoutName}.txt";
            if (file_exists($filename)) $files[] = new Source($filename);
        }
        return $files;
    }
}
