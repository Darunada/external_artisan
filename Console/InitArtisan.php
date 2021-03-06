<?php

namespace Darunada\Console;

use Illuminate\Filesystem\Filesystem;
use Pimple\Container;
use Symfony\Component\Console\Application;
use Medoo\Medoo;

class InitArtisan
{

    private $kernelClass = null;

    /**
     * InitArtisan constructor.
     * @param $kernelClass
     */
    public function __construct($kernelClass)
    {
        $this->kernelClass = $kernelClass;
    }

    /**
     * Init fake Artisan
     * @param  String $addName Artisan Display name
     * @param  String $appVersion Artisan Display version
     * @return Application
     */
    public function init($addName, $appVersion)
    {
        $kernel = new $this->kernelClass();
        $app = new Application($addName, $appVersion);

        foreach ($kernel->getCommands() as $command => $path) {
            $container = new Container();

            $container['filesystem'] = function (Container $c) {
                $filesystem = new Filesystem();
                return $filesystem;
            };

            $container['details'] = function (Container $c) use ($path) {
                $console = new $path($c);
                $console->setLaravel(new FakeLaravel($console));
                return $console;
            };

            $container['database'] = function(Container $c) {
                $config = [
                    'database_type' => 'mysql',
                    'database_name' => getenv('DATABASE_NAME'),
                    'server' => getenv('DATABASE_HOSTNAME'),
                    'port' => getenv('DATABASE_PORT'),
                    'username' => getenv('DATABASE_USERNAME'),
                    'password' => getenv('DATABASE_PASSWORD'),
                    'charset' => 'utf8'
                ];
                return new Medoo($config);
            };

            $app->add($container['details']);
        }

        return $app;
    }
}