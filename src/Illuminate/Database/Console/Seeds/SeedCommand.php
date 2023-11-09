<?php

namespace Sonole\LaravelDbSeedRollback\Illuminate\Database\Console\Seeds;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use ReflectionClass;

#[AsCommand(name: 'db:seed')]
class SeedCommand extends \Illuminate\Database\Console\Seeds\SeedCommand
{
    /** The file to delete after command is executed */
    private ?string $fileToDelete;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        Model::unguarded(function () {
            $params = $this->input->getOption('rollback') ? ['rollback'] : [];
            $this->getSeeder()->__invoke($params);
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        if(isset($this->fileToDelete)) {
            \File::delete($this->fileToDelete);
        }

        return 0;
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Sonole\LaravelDbSeedRollback\Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->input->getArgument('class') ?? $this->input->getOption('class');

        if (! str_contains($class, '\\')) {
            $class = 'Database\\Seeders\\'.$class;
        }

        if ($class === 'Database\\Seeders\\DatabaseSeeder' &&
            ! class_exists($class)) {
            $class = 'DatabaseSeeder';
        }

        if (! $this->input->getOption('rollback') === true) {
            return $this->laravel->make($class)
                ->setContainer($this->laravel)
                ->setCommand($this);
        }

        //Create a temporary file which will extend the desired class that we need to invoke.
        $tempClassName = $class . 'ExtendsRollback';
        if(str_contains($tempClassName, '\\')) {
            $arr = explode('\\', $tempClassName);
            $tempClassName = end($arr);
        }
        $tempFilePath = database_path('seeders' . DIRECTORY_SEPARATOR . $tempClassName . '.php');
        $reflector = new ReflectionClass($class);
        $existingClassContents = file_get_contents($reflector->getFileName());

        //create file only if contents does not contain the namespace
        if(!str_contains($existingClassContents, 'Sonole\LaravelDbSeedRollback\Illuminate\Database')) {
            $pattern = '/namespace [^;]+;/';
            $replacement = 'namespace Database\Seeders;';
            $newClassContents = preg_replace($pattern, $replacement, $existingClassContents);

            $pattern = '/class (\w+) extends [^\s]+/';
            $replacement = 'class ' . $tempClassName . ' extends \Sonole\LaravelDbSeedRollback\Illuminate\Database\Seeder';
            $newClassContents = preg_replace($pattern, $replacement, $newClassContents);
            file_put_contents($tempFilePath, $newClassContents);

            if(\File::exists($tempFilePath)) {
                $this->fileToDelete = $tempFilePath;
                return $this->laravel->make('Database\\Seeders\\' .$tempClassName)
                    ->setContainer($this->laravel)
                    ->setCommand($this);
            }
        }

        return $this->laravel->make($class)
            ->setContainer($this->laravel)
            ->setCommand($this);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['rollback', null, InputOption::VALUE_NONE, 'To run the rollback function which aims to revert any db changes made'],
        ];
    }
}