<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1ca780e73208dd94bb11c29277e8a2ca
{
    public static $files = array (
        '297e7ce59d65f7316d640078bfebbff4' => __DIR__ . '/../..' . '/lib/transformer.php',
        'a03153c6cf2b312c3df9bf36f7514011' => __DIR__ . '/../..' . '/lib/calc.php',
        '2805248c679f71d5dfae9974502bb96c' => __DIR__ . '/../..' . '/lib/util.php',
        'e7ba5631e4fb8d1a94fe51ac133fafb8' => __DIR__ . '/../..' . '/mount_rp.php',
    );

    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1ca780e73208dd94bb11c29277e8a2ca::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1ca780e73208dd94bb11c29277e8a2ca::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1ca780e73208dd94bb11c29277e8a2ca::$classMap;

        }, null, ClassLoader::class);
    }
}
