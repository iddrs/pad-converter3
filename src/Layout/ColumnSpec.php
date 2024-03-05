<?php

namespace App\Layout;

/**
 * Especificação da coluna do layout
 *
 * @author Everton
 */
class ColumnSpec {
    private \DOMNode $node;
    
    public readonly string $origin;
    
    public readonly string $id;
    
    public readonly ?int $len;
    
    public readonly ?int $start;
    
    public readonly string $type;
    
    public readonly ?string $transformer;
    
    public readonly ?string $fn;
    
    public readonly ?string $prop;

    public function __construct(\DOMNode $node) {
        $this->node = $node;
        $this->loadSpec();
    }
    
    private function loadSpec(): void {
        $this->origin = (string) $this->node->getAttribute('origin');
        $this->id = (string) $this->node->getAttribute('id');
        $this->type = (string) $this->node->getAttribute('type');
        
        if ($this->node->getAttribute('len')) {
            $this->len = (int) $this->node->getAttribute('len');
        } else {
            $this->len = null;
        }
        
        if ($this->node->getAttribute('start')) {
            $this->start = (int) $this->node->getAttribute('start');
        } else {
            $this->start = null;
        }
        
        if ($this->node->getAttribute('transformer')) {
            $this->transformer = (string) $this->node->getAttribute('transformer');
        } else {
            $this->transformer = null;
        }
        
        if ($this->node->getAttribute('fn')) {
            $this->fn = (string) $this->node->getAttribute('fn');
        } else {
            $this->fn = null;
        }
        
        if ($this->node->getAttribute('prop')) {
            $this->prop = (string) $this->node->getAttribute('prop');
        } else {
            $this->prop = null;
        }
    }
}
