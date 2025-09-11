<?php

namespace Everton3x\AutoloadFilesPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;
use Symfony\Component\Finder\Finder;

class AutoloadFilesPlugin implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'onPreAutoloadDump',
        ];
    }

    public function onPreAutoloadDump(Event $event)
    {
        $package = $this->composer->getPackage();
        $extra = $package->getExtra();

        // Verifica se há diretórios configurados
        if (!isset($extra['autoload-files-from']) || !is_array($extra['autoload-files-from'])) {
            return;
        }

        $autoload = $package->getAutoload();
        $autoload['files'] = $autoload['files'] ?? [];

        foreach ($extra['autoload-files-from'] as $dir) {
            $dirPath = realpath($dir);

            if (!$dirPath || !is_dir($dirPath)) {
                $this->io->writeError("<warning>Diretório não encontrado: $dir</warning>");
                continue;
            }

            // Encontra todos os arquivos .php no diretório
            $finder = new Finder();
            $files = $finder->in($dirPath)->name('*.php')->files();

            foreach ($files as $file) {
                $relativePath = $this->getRelativePath($file->getRealPath());
                if (!in_array($relativePath, $autoload['files'])) {
                    $autoload['files'][] = $relativePath;
                    $this->io->write("Adicionado ao autoload: " . $relativePath);
                }
            }
        }

        $package->setAutoload($autoload);
    }

    private function getRelativePath($absolutePath)
    {
        $packageRoot = realpath(dirname($this->composer->getConfig()->getConfigSource()->getName()));
        return str_replace($packageRoot . DIRECTORY_SEPARATOR, '', $absolutePath);
    }

    public function deactivate(Composer $composer, IOInterface $io) {}

    public function uninstall(Composer $composer, IOInterface $io) {}
}
