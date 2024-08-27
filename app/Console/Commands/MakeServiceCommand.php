<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeServiceCommand extends GeneratorCommand
{
    /**
     * STUB_PATH.
     */
    const STUB_PATH = __DIR__ . '/stubs/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name : Create a service class} {--model= : The model name}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class and contract';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Service';

    protected function getStub() {}

    /**
     * @return string
     */
    protected function getServiceStub(): string
    {
        $stub ??= '/stubs/service.stub';
        return $this->resolveStubPath($stub);
    }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    protected function getInterfaceStub(): string
    {
        return self::STUB_PATH . 'interface.stub';
    }

    /**
     * @return string
     */
    protected function getServiceInterfaceStub(): string
    {
        return self::STUB_PATH . 'service.interface.stub';
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @see \Illuminate\Console\GeneratorCommand
     *
     */
    public function handle()
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "' . $this->getNameInput() . '" is reserved by PHP.');

            return false;
        }

        $modelName = $this->option('model');

        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if ((! $this->hasOption('force') ||
                ! $this->option('force')) &&
            $this->alreadyExists($this->getNameInput())
        ) {
            $this->error($this->type . ' already exists!');

            return false;
        }

        $this->makeDirectory($path);

        $this->files->put(
            $path,
            $this->sortImports(
                $this->buildServiceClass($name, $modelName)
            )
        );
        $message = $this->type;

        $this->info($message . ' created successfully.');
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildServiceClass(string $name, string $model): string
    {
        $stub = $this->files->get($this->getServiceStub());
        $stub = $this->replaceModel($stub, $model);
        $stub = $this->replaceNamespacedModel($stub, $this->getModelNamespace($model));
        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        return str_replace(['DummyModel', '{{ model }}', '{{model}}'], $model, $stub);
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $namespacedModel
     * @return string
     */
    protected function replaceNamespacedModel($stub, $namespacedModel)
    {
        return str_replace(
            ['DummyNamespacedModel', '{{namespacedModel}}', '{{ namespacedModel }}'],
            $namespacedModel,
            $stub
        );
    }

    /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function getModelNamespace(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace . 'Models\\' . $model
            : $rootNamespace . $model;
    }

    /**
     * @param $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Services';
    }
}
