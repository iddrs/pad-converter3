<?php

namespace App\Layout;

/**
 * Acesso a layout de arquivo txt do PAD.
 *
 * @author Everton
 */
class Layout {
    
    private readonly \DOMDocument $dom;
    
    public function __construct(\DOMDocument $dom) {
        $this->dom = $dom;
    }
    
    public function getLayoutName(): string {
        return (string) $this->dom->documentElement->getAttribute('name');
    }
    
    public function getFileName(): string {
        return (string) $this->dom->baseURI;
    }
    
    public function getColumns(): array {
        $cols = $this->dom->documentElement->getElementsByTagName('col');
        $column_list = [];
        foreach ($cols as $col) {
            $column_list[] = new ColumnSpec($col);
        }
        return $column_list;
    }
}
