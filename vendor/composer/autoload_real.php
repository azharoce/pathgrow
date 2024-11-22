<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit4c23cd2c041e8e2cf2e37e8df895d22b
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit4c23cd2c041e8e2cf2e37e8df895d22b', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit4c23cd2c041e8e2cf2e37e8df895d22b', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit4c23cd2c041e8e2cf2e37e8df895d22b::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
