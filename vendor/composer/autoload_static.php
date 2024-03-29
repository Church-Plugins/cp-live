<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf6292abe59d73c1e1e0f956217b316b7
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPackio\\' => 8,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
            'CP_Live\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPackio\\' => 
        array (
            0 => __DIR__ . '/..' . '/wpackio/enqueue/inc',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
        'CP_Live\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf6292abe59d73c1e1e0f956217b316b7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf6292abe59d73c1e1e0f956217b316b7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf6292abe59d73c1e1e0f956217b316b7::$classMap;

        }, null, ClassLoader::class);
    }
}
