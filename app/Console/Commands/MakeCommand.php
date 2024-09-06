<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Blueprint\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\Pipe;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:make
                            {--namespace= : The namespace name. E.g. V1}
                            {--resource= : The Resource name}
                            {--controller= : The Controller name}
                            {--model= : The Model name}
                            {--template= : Generate model and migrations}
                            {attributes?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model, migration, controller and add routes';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * @var Composer
     */
    private $composer;


    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     * @param Builder $builder;
     */
    public function __construct(
        Filesystem $files,
        Composer $composer,
    ) {
        parent::__construct();

        $this->files = $files;

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $namespace = $this->option('namespace');
        $resource = $this->option('resource');
        $controller = $this->option('controller');
        $model = $this->option('model');
        $template = $this->option('template');

        if (!is_null($template)) {
            $this->files->put($this->defaultDraftFile(), $template);
            $this->runBluePrint($namespace);
            foreach ($this->getModels() as $model) {
                $this->start($namespace, $model, $controller, $model);
            }
            return;
        }

        $this->start($namespace, $resource, $controller, $model);
        // $this->appendRoutes($name);
    }

    private function start(
        string $namespace,
        string $resource,
        string $controller,
        string $model
    ): void {
        if (!is_null($resource)) {
            $this->createService($namespace, $resource, Str::singular($model ?? $resource));
        }
        if (!is_null($controller)) {

            $this->createController(
                namespace: $namespace,
                controller: $controller,
                resource: $resource
            );

            $this->createResource(
                namespace: $namespace,
                resource: $resource,
                model: $model ?? $resource
            );
        }
    }

    private function runBluePrint(string $namespace): void
    {
        $result =  Process::pipe(function (Pipe $pipe) {
            $pipe->command('php artisan blueprint:build');
            $pipe->command('php artisan migrate');
        });
        if ($result->successful()) {
            sleep(2);
            \Illuminate\Support\Facades\Schema::refreshDatabaseSchema();
            foreach ($this->getModels() as $model) {
                $this->createRequest(namespace: $namespace, resource: $model);
            }
            //Artisan::call(command: 'migrate:rollback');
        } else {
            $this->error('Failed to run blueprint:build or migrate');
        }
    }

    private function getModels(): array
    {
        $blueprint = resolve(Blueprint::class);
        $contents = $this->files->get($this->defaultDraftFile());
        $using_indexes = preg_match('/^\s+indexes:\R/m', $contents) !== 1;

        $tokens = $blueprint->parse($contents, $using_indexes);
        $models = array_keys($tokens['models']);
        //$registry = $blueprint->analyze($tokens);
        return $models;
    }

    private function defaultDraftFile(): string
    {
        return file_exists('draft.yml') ? 'draft.yml' : 'draft.yaml';
    }

    public function buildResourceAttributes($attributes)
    {
        $resource = '';
        foreach ($attributes as $attribute) {
            $resource .= "'" . $attribute . "' => \$this->resource->" . $attribute . "," . PHP_EOL . '';
        }
        return rtrim($resource);
    }


    private function createController(string $namespace, string $controller, null|string $resource)
    {
        $rootNamespace = sprintf('%s', str_replace('/', '\\', 'Http/Controllers/Api/'));

        $path =  (!is_null($namespace)) ? $rootNamespace . $namespace : $rootNamespace;

        $files = $this->extractContentFile(
            path: $path,
            controller: $controller,
            namespace: $namespace,
            resource: $resource
        );

        foreach ($files as $file) {
            $filename = str_replace('\\', '/', $file['file']);

            $directory = dirname(app_path($filename));
            if (!$this->files->exists($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
            }

            $this->files->put(app_path($filename), $file['stub']);
            $this->info('Created controller ' . $file['name']);
        }

        return true;
    }

    private function createRequest(string $namespace, string $resource)
    {
        foreach (['Store', 'Update'] as $key => $action) {
            $this->callSilently(
                command: 'schema:generate-rules',
                arguments: [
                    'table' => Str::plural(strtolower($resource)),
                    '--create-request' => true,
                    '--file' => $this->buildFilePath(
                        type: 'request',
                        resource: $resource,
                        namespace: $namespace,
                        action: $action
                    )
                ]
            );
        }
        return true;
    }

    private function createResource(string $namespace, string $resource, string $model): bool
    {
        $namespace = str_replace('/', '\\', $this->getRootNamespace(
            rootNamespace: 'Http/Resources/Api',
            namespace: $namespace,
            resource: $resource
        ));

        $className = sprintf('%sResource', Str::plural(ucfirst($resource)));
        $filename = sprintf($namespace . '\\%sResource.php', Str::plural(ucfirst($resource)));
        $namespacedModel = $this->getModelNamespace(inModel: false, model: $model);

        if (windows_os()) {
            $filename = str_replace('/', '\\', $filename);
        }

        $filename = str_replace('\\', '/', $filename);
        if ($this->files->exists(app_path($filename))) {
            $this->error("$className Resource already exists!");
            return false;
        }

        $stub = $this->files->get($this->getStubs('resource'));
        $stub = $this->replaceNamespace($namespace,  $stub);
        $stub = $this->replaceClassName($className, $stub);
        $stub = str_replace('{{ namespacedModel }}', $namespacedModel, $stub);
        $stub = str_replace('{{ model }}', Str::singular($model), $stub);


        if (!is_null($namespacedModel)) {
            $class = $namespacedModel;
            $model = new $class;
            $stub = str_replace(
                search: '{{ ATTRIBUTES }}',
                replace: $this->buildResourceAttributes($model->getFillable()),
                subject: $stub
            );
        } else {
            $stub = str_replace(
                search: '{{ ATTRIBUTES }}',
                replace: '',
                subject: $stub
            );
        }

        $directory = dirname(app_path($filename));
        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put(app_path($filename), $stub);
        $this->info('Created Resource ' . $filename);
        return true;
    }

    private function createService(null|string $namespace, string $resource, string $model): bool
    {
        $path =  $this->getRootNamespace(
            rootNamespace: 'Services',
            namespace: $namespace,
            resource: $resource
        );

        $filename = $this->generatePathService(path: $path, resource: $resource);

        if (windows_os()) {
            $filename = str_replace('/', '\\', $filename);
        }
        $filename = str_replace('\\', '/', $filename);
        if ($this->files->exists(app_path($filename))) {
            $this->error('Service already exists!');
            return false;
        }

        $stub = $this->files->get($this->getStubs('service'));

        $namespacedModel = !is_null($model)
            ? $this->getModelNamespace(
                inModel: false,
                model: $model
            )
            : $this->error('Indique modelo para servicio');

        $namespace = sprintf($this->rootNamespace() . '%s', str_replace('/', '\\', $path));
        $className = sprintf('%sService', ucfirst($resource));

        $stub = $this->replaceClassName($className, $stub);
        $stub = $this->replaceNamespace($namespace,  $stub);
        $stub = str_replace('{{ namespacedModel }}', ucfirst($namespacedModel), $stub);
        $stub = str_replace('{{ model }}', Str::singular($model), $stub);

        $directory = dirname(app_path($filename));
        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put(app_path($filename), $stub);

        $this->info('Created Service ' . $filename);

        return true;
    }

    private function getRootNamespace(
        string $rootNamespace,
        null|string $namespace,
        null|string $resource
    ): string {

        $path = (!is_null($namespace)) ? $rootNamespace . '\\' . $namespace : $rootNamespace;

        if (!is_null($resource)) {
            $path = sprintf($path . '\\%s', Str::plural(ucfirst($resource)));
        }

        return $path;
    }

    private function generatePathService(string $path, string $resource): string
    {
        return sprintf($path . '/%sService.php', ucfirst($resource));
    }

    private function appendRoutes($modelName)
    {
        $modelTitle = ucfirst($modelName);

        $modelName = strtolower($modelName);

        $newRoutes = $this->files->get(__DIR__ . '/../Stubs/routes.stub');

        $newRoutes = str_replace('|MODEL_TITLE|', $modelTitle, $newRoutes);

        $newRoutes = str_replace('|MODEL_NAME|', $modelName, $newRoutes);

        $newRoutes = str_replace('|CONTROLLER_NAME|', $modelTitle . 'Controller', $newRoutes);

        $this->files->append(
            app_path('Http/routes.php'),
            $newRoutes
        );

        $this->info('Added routes for ' . $modelTitle);
    }

    private function replaceClassName(string $className, string $stub)
    {
        return str_replace('{{ class }}', $className, $stub);
    }

    private function replaceNamespace(string $namespace, string $stub)
    {
        return str_replace('{{ namespace }}', ucfirst($namespace), $stub);
    }

    /**
     * Convert a pipe-separated list of attributes to an array.
     *
     * @param string $text The Pipe separated attributes
     * @return array
     */
    public function parseAttributesFromInputString($text)
    {
        $parts = explode('|', $text);

        $attributes = [];

        foreach ($parts as $part) {
            $components = explode(':', $part);
            $attributes[$components[0]] =
                isset($components[1]) ? explode(',', $components[1]) : [];
        }

        return $attributes;
    }

    /**
     * Build a Model name from a word.
     *
     * @param string $name
     * @return string
     */
    private function modelName($name)
    {
        return ucfirst($name);
    }

    /**
     * @params string $fileName
     * @return string
     */
    protected function getStubs(string $fileName): string
    {
        $stub ??= "/stubs/$fileName.stub";
        return $this->resolveStubPath($stub);
    }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace();
    }

    /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function getModelNamespace(bool $inModel = false, string $model): string
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        if ($inModel) {
            return $rootNamespace . 'Models';
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace . 'Models\\' . $model
            : $rootNamespace . $model;
    }

    private function extractContentFile(
        string $path,
        string $controller,
        string $namespace,
        null|string $resource
    ): array {

        $arrayFiles = [];
        foreach (['Index'] as $key => $action) {
            $namespaceController = sprintf('App\\' . $path . '\\%s', Str::plural(ucfirst($controller)));
            $namespaceService = $this->getRootNamespace(
                rootNamespace: 'App\\Services',
                namespace: $namespace,
                resource: $resource
            );

            $service = !is_null($resource) ? sprintf('%sService', ucfirst($resource)) : null;
            $_resource = Str::plural(ucfirst($resource));
            $controller = sprintf('%s%sController', $_resource, $action);
            $file = sprintf($path . '/%s/%sController.php', $_resource, $_resource . $action);

            $resourceNamespace = str_replace('/', '\\', $this->getRootNamespace(
                rootNamespace: 'Http/Resources/Api',
                namespace: $namespace,
                resource: $_resource
            ));
            $resourceNamespace = sprintf($resourceNamespace . '\\%sResource', $_resource);

            $stub = $this->files->get(
                path: $this->getStubs('controller.' . strtolower($action))
            );

            $stub = $this->stubsControllerReplace(
                stub: $stub,
                controller: $controller,
                namespaceController: $namespaceController,
                namespacedService: $namespaceService . '\\' . $service,
                service: $service,
                namespacedResource: $resourceNamespace,
                resource: sprintf('%sResource', Str::plural(ucfirst($resource)))
            );
            $file = str_replace('\\', '/', $file);
            if ($this->files->exists(app_path($file))) {
                $this->error($controller . " already exists!");
            } else {
                if (windows_os()) {
                    $arrayFiles[$key] = [
                        'name' => $controller,
                        'file' => $file,
                        'stub' => $stub
                    ];
                } else {
                    $arrayFiles[$key] = [
                        'name' => $controller,
                        'file' => $file,
                        'stub' => $stub
                    ];
                }
            }
        }

        return $arrayFiles;
    }

    private function stubsControllerReplace(
        string $stub,
        string $controller,
        string $namespaceController,
        string $namespacedService,
        null|string $service,
        string $namespacedResource,
        string $resource
    ): string {
        $stub = str_replace('{{ namespace }}', $namespaceController, $stub);
        $stub = str_replace('{{ namespacedService }}', $namespacedService, $stub);
        $stub = str_replace('{{ class }}', $controller, $stub);
        $stub = str_replace('{{ service }}', $service, $stub);
        $stub = str_replace('{{ namespacedResource }}', $namespacedResource, $stub);
        $stub = str_replace('{{ resource }}', $resource, $stub);

        return $stub;
    }
    private function buildFilePath(
        string $type,
        string $resource,
        string $namespace,
        string $action = null
    ): string {
        return match ($type) {
            'controller' => sprintf('Api/%s/%s%sController', $resource, $resource, $action),
            'request'    => sprintf('Api/%s/%s/%s%sRequest', $namespace, Str::plural($resource), Str::plural($resource), $action),
            'resource'   => sprintf('%s/%sResource', $namespace, Str::plural($resource)),
            default      => sprintf('Http/Controllers/Api/%s/%s/%s%sControllerTest', $namespace, $resource, $resource, $action),
        };
    }
}
