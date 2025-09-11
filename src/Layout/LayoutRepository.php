<?php

namespace PadConverter\Layout;

final class LayoutRepository
{

    public readonly string $layoutDir;

    public function __construct(string $layoutDir)
    {
        $this->layoutDir = $layoutDir;
    }

    /**
     * Dados de um layout específico.
     * 
     * @param string $layoutName
     * @return Layout
     */
    public function getLayoutFor(string $layoutName): Layout
    {
        $dom = new \DOMDocument();
        $file = "{$this->layoutDir}{$layoutName}.xml";
        if (!$dom->load($file)) {
            trigger_error("Falha ao ler o layout [$layoutName] de [$file].", E_USER_ERROR);
        }
        return new Layout($dom);
    }

    /**
     * Lista com os nomes dos layouts encontrados.
     * 
     * @return array
     */
    public function getLayoutNames(): array
    {
        $list_files = glob("{$this->layoutDir}*.xml");
        $name_list = [];
        foreach ($list_files as $file_name) {
            $name_list[] = basename($file_name, '.xml');
        }
        return $name_list;
    }
}
