<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

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
     * @var array The data types that can be created in a migration.
     */
    private $dataTypes = [
        'string',
        'integer',
        'boolean',
        'bigIncrements',
        'bigInteger',
        'binary',
        'boolean',
        'char',
        'date',
        'dateTime',
        'float',
        'increments',
        'json',
        'jsonb',
        'longText',
        'mediumInteger',
        'mediumText',
        'nullableTimestamps',
        'smallInteger',
        'tinyInteger',
        'softDeletes',
        'text',
        'time',
        'timestamp',
        'timestamps',
        'rememberToken',
    ];

    private $fakerMethods = [
        'string' => ['method' => 'words', 'parameters' => '2, true'],
        'integer' => ['method' => 'randomNumber', 'parameters' => ''],
    ];

    /**
     * @var array $columnProperties Properties that can be applied to a table column.
     */
    private $columnProperties = [
        'unsigned',
        'index',
        'nullable'
    ];

    /**
     * @param $rootNamespace
     */
    private $rootNamespaceService = 'Services';

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
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

        if (!is_null($resource) && !is_null($model)) {
            $this->createService($namespace, $resource, Str::singular($model));
        }

        if (!is_null($model)) {
            $this->createModel(name: Str::singular($model));
            $this->createMigration(name: Str::singular($model));
            $this->createModelFactory(name: Str::singular($model));
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
                model: Str::singular($model ?? $resource)
            );
        }

        // $this->appendRoutes($name);

    }

    private function createModelFactory(string $name)
    {
        $model = $this->modelName($name);

        $stub = $this->files->get($this->getStubs(fileName: 'factory'));

        $namespacedModel = $this->getModelNamespace(
            inModel: false,
            model: Str::singular($model)
        );
        $stub = str_replace('{{ namespacedModel }}', $namespacedModel, $stub);
        $stub = str_replace('{{ model }}', $model, $stub);
        $filename = sprintf('factories/%sFactory.php', ucfirst($model));

        $class = $namespacedModel;
        $model = new $class;

        if (!is_null($this->argument('attributes'))) {
            $stub = str_replace(
                search: 'ATTRIBUTES',
                replace: $this->buildFakerAttributes($model->migrationAttributes()),
                subject: $stub
            );
        }

        if ($this->files->exists(database_path($filename))) {
            $this->error('Model Factory already exists!');
            return false;
        }

        $this->files->append(database_path($filename), $stub);

        $this->info('Created model factory');

        return true;
    }

    public function buildFakerAttributes($attributes)
    {
        $faker = '';

        foreach ($attributes as $attribute) {

            $formatter =
                $this->fakerMethods[$this->getFieldTypeFromProperties($attribute['properties'])];

            $method = $formatter['method'];
            $parameters = $formatter['parameters'];

            $faker .= "'" . $attribute['name'] . "' => fake()->" . $method . "(" . $parameters . ")," . PHP_EOL . '        ';
        }

        return rtrim($faker);
    }

    public function buildResourceAttributes($attributes)
    {
        $resource = '';
        foreach ($attributes as $attribute) {
            $resource .= "'" . $attribute['name'] . "' => \$this->resource->" . $attribute['name'] . "," . PHP_EOL . '        ';
        }
        return rtrim($resource);
    }

    /**
     * Create and store a new Model to the filesystem.
     *
     * @param string $name
     * @return bool
     */
    private function createModel(string $name)
    {
        $modelName = $this->modelName($name);

        $filename = $modelName . '.php';
        $filename_path = app_path('Models/' . $filename);

        if ($this->files->exists($filename_path)) {
            $this->error('Model already exists!');
            return false;
        }

        $directory = dirname($filename_path);
        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $model = $this->buildModel($name);

        $this->files->put($filename_path, $model);

        $this->info($modelName . ' Model created');

        return true;
    }

    private function createMigration(string $name)
    {
        $filename = $this->buildMigrationFilename($name);

        if ($this->files->exists(database_path($filename))) {
            $this->error('Migration already exists!');
            return false;
        }

        $migration = $this->buildMigration($name);

        $this->files->put(
            database_path('/migrations/' . $filename),
            $migration
        );

        if (env('APP_ENV') != 'testing') {
            $this->composer->dumpAutoloads();
        }

        $this->info('Created migration ' . $filename);

        return true;
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

    private function createResource(string $namespace, string $resource, string $model): bool
    {
        $namespace = str_replace('/', '\\', $this->getRootNamespace(
            rootNamespace: 'Http/Resources/Api',
            namespace: $namespace,
            resource: $resource
        ));

        $className = sprintf('%sResource', ucfirst($resource));
        $filename = sprintf($namespace . '\\%sResource.php', ucfirst($resource));
        $namespacedModel = $this->getModelNamespace(inModel: false, model: $model);

        if (windows_os()) {
            $filename = str_replace('/', '\\', $filename);
        }

        $filename = str_replace('\\', '/', $filename);
        if ($this->files->exists(app_path($filename))) {
            $this->error("$resource Resource already exists!");
            return false;
        }

        $stub = $this->files->get($this->getStubs('resource'));
        $stub = $this->replaceNamespace($namespace,  $stub);
        $stub = $this->replaceClassName($className, $stub);
        $stub = str_replace('{{ namespacedModel }}', $namespacedModel, $stub);
        $stub = str_replace('{{ model }}', Str::singular($model), $stub);

        if (!is_null($this->argument('attributes'))) {
            $class = $namespacedModel;
            $model = new $class;
            $stub = str_replace(
                search: '{{ ATTRIBUTES }}',
                replace: $this->buildResourceAttributes($model->migrationAttributes()),
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

        if ($this->files->exists(app_path($filename))) {
            $this->error('Service already exists!');
            return false;
        }

        $stub = $this->files->get($this->getStubs('service'));

        $namespacedModel = !is_null($model)
            ? $this->getModelNamespace(
                inModel: false,
                model: Str::singular($model)
            )
            : $this->error('Indique modelo para servicio');

        $namespace = sprintf($this->rootNamespace() . '%s', str_replace('/', '\\', $path));
        $className = sprintf('%sService', ucfirst($resource));

        $stub = $this->replaceClassName($className, $stub);
        $stub = $this->replaceNamespace($namespace,  $stub);
        $stub = str_replace('{{ namespacedModel }}', ucfirst($namespacedModel), $stub);
        $stub = str_replace('{{ model }}', Str::singular($model), $stub);

        $filename = str_replace('\\', '/', $filename);
        $directory = dirname(app_path($filename));
        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put(app_path($filename), $stub);

        $this->info('Created controller ' . $filename);

        return true;
    }

    private function getRootNamespace(
        string $rootNamespace,
        null|string $namespace,
        null|string $resource
    ): string {

        $path = (!is_null($namespace)) ? $rootNamespace . '\\' . $namespace : $rootNamespace;

        if (!is_null($resource)) {
            $path = sprintf($path . '\\%s', ucfirst($resource));
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

    protected function buildMigration(string $name)
    {
        $stub = '';
        $class = $this->getModelNamespace(inModel: false, model: $name);
        $model = new $class;

        if (!is_null($this->argument('attributes'))) {
            $stub = $this->files->get(path: $this->getStubs(fileName: 'migration.create'));
            $stub = str_replace(
                search: '{{ MIGRATION_COLUMNS_PLACEHOLDER }}',
                replace: $this->buildTableColumns(
                    attributes: $model->migrationAttributes()
                ),
                subject: $stub
            );
        } else {
            $stub = $this->files->get(path: $this->getStubs('migration.clear'));
        }

        $table = strtolower(Str::plural($name));

        $stub = str_replace('{{ table }}', $table, $stub);

        return $stub;
    }

    protected function buildModel($name)
    {
        if (!is_null($this->argument('attributes'))) {
            $stub = $this->files->get(path: $this->getStubs('model.attributes'));
            $stub = $this->replaceClassName(className: $name, stub: $stub);
            $stub = $this->replaceNamespace(
                $this->getModelNamespace(inModel: true, model: $name),
                stub: $stub
            );

            $stub = $this->addMigrationAttributes($this->argument('attributes'), $stub);

            $stub = $this->addModelAttributes(
                $this->argument('attributes'),
                $stub
            );

            $stub = $this->addModelHiddenAttributes(
                $this->argument('attributes'),
                $stub
            );

            return $stub;
        }

        $stub = $this->files->get(path: $this->getStubs('model'));

        $stub = $this->replaceClassName(className: $name, stub: $stub);

        $stub = $this->replaceNamespace(
            namespace: $this->getModelNamespace(inModel: true, model: $name),
            stub: $stub
        );

        return $stub;
    }

    public function convertModelToTableName($model)
    {
        return Str::plural(Str::snake($model));
    }

    public function buildMigrationFilename($model)
    {
        $table = $this->convertModelToTableName($model);

        return date('Y_m_d_his') . '_create_' . $table . '_table.php';
    }

    private function replaceClassName(string $className, string $stub)
    {
        return str_replace('{{ class }}', $className, $stub);
    }

    private function replaceNamespace(string $namespace, string $stub)
    {
        return str_replace('{{ namespace }}', ucfirst($namespace), $stub);
    }

    private function addMigrationAttributes($text, $stub)
    {
        $attributesAsArray = $this->parseAttributesFromInputString($text);
        $attributesAsText = $this->convertArrayToString($attributesAsArray);

        return str_replace('MIGRATION_ATTRIBUTES_PLACEHOLDER', $attributesAsText, $stub);
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
     * Convert a PHP array into a string version.
     *
     * @param $array
     *
     * @return string
     */
    public function convertArrayToString($array)
    {
        $string = '[';

        foreach ($array as $name => $properties) {
            $string .= '[';
            $string .= "'name' => '" . $name . "',";

            $string .= "'properties' => [";
            foreach ($properties as $property) {
                $string .= "'" . $property . "', ";
            }
            $string = rtrim($string, ', ');
            $string .= ']';

            $string .= '],';
        }

        $string = rtrim($string, ',');

        $string .= ']';


        return $string;
    }

    public function addModelAttributes($attributes, $stub): string
    {
        $attributes = implode(
            ', ',
            array_map(function ($attribute) {
                return "'" . $attribute . "'";
            }, array_keys($this->parseAttributesFromInputString($attributes)))
        );
        return str_replace('{{ fillable }}', $attributes, $stub);
    }

    public function addModelHiddenAttributes($attributes, $stub): string
    {
        $hiddenAttributes = collect($this->parseAttributesFromInputString($attributes))
            ->filter(fn($attribute) => in_array('hidden', $attribute))
            ->map(fn($attribute) => $attribute)
            ->keys()->toArray();

        $hidden = implode(
            ', ',
            array_map(function ($attribute) {
                return "'" . $attribute . "'";
            }, $hiddenAttributes)
        );

        return str_replace('{{ hidden }}', $hidden, $stub);
    }

    public function buildTableColumns($attributes)
    {

        return rtrim(collect($attributes)->reduce(function ($column, $attribute) {

            $fieldType = $this->getFieldTypeFromProperties($attribute['properties']);

            if ($length = $this->typeCanDefineSize($fieldType)) {
                $length = $this->extractFieldLengthValue($attribute['properties']);
            }

            $properties = $this->extractAttributePropertiesToApply($attribute['properties']);

            return $column . $this->buildSchemaColumn($fieldType, $attribute['name'], $length, $properties);
        }));
    }

    /**
     * Get the column field type based from the properties of the field being created.
     *
     * @param array $properties
     * @return string
     */
    private function getFieldTypeFromProperties($properties)
    {
        $type = array_intersect($properties, $this->dataTypes);

        // If the properties that were given in the command
        // do not explicitly define a data type, or there
        // is no matching data type found, the column
        // should be cast to a string.

        if (! $type) {
            return 'string';
        }

        return $type[0];
    }

    /**
     * Can the data type have it's size controlled within the migration?
     *
     * @param string $type
     * @return bool
     */
    private function typeCanDefineSize($type)
    {
        return $type == 'string' || $type == 'char';
    }

    /**
     * Extract a numeric length value from all properties specified for the attribute.
     *
     * @param array $properties
     * @return int $length
     */
    private function extractFieldLengthValue($properties)
    {
        foreach ($properties as $property) {
            if (is_numeric($property)) {
                return $property;
            }
        }

        return 0;
    }

    /**
     * Get the column properties that should be applied to the column.
     *
     * @param $properties
     * @return array
     */
    private function extractAttributePropertiesToApply($properties)
    {
        return array_intersect($properties, $this->columnProperties);
    }

    /**
     * Create a Schema Builder column.
     *
     * @param string $fieldType The type of column to create
     * @param string $name Name of the column to create
     * @param int $length Field length
     * @param array $traits Additional properties to apply to the column
     * @return string
     */
    private function buildSchemaColumn($fieldType, $name, $length = 0, $traits = [])
    {
        return sprintf(
            "\$table->%s('%s'%s)%s;" . PHP_EOL . '            ',
            $fieldType,
            $name,
            $length > 0 ? ", $length" : '',
            implode('', array_map(function ($trait) {
                return '->' . $trait . '()';
            }, $traits))
        );
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
            $namespaceController = sprintf('App\\' . $path . '\\%s', ucfirst($controller));
            $namespaceService = $this->getRootNamespace(
                rootNamespace: 'App\\Services',
                namespace: $namespace,
                resource: $resource
            );

            $controller = sprintf('%sController', $action);
            $service = !is_null($resource) ? sprintf('%sService', ucfirst($resource)) : null;
            $file = sprintf($path . '/%s/%sController.php', ucfirst($resource), $action);

            $resourceNamespace = str_replace('/', '\\', $this->getRootNamespace(
                rootNamespace: 'Http/Resources/Api',
                namespace: $namespace,
                resource: $resource
            ));
            $resourceNamespace = sprintf($resourceNamespace . '\\%sResource', ucfirst($resource));

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
                resource: sprintf('%sResource', ucfirst($resource))
            );

            if ($this->files->exists(app_path($file))) {
                $this->error($action . "Controller already exists!");
            } else {
                if (windows_os()) {
                    $arrayFiles[$key] = [
                        'name' => $action,
                        'file' => str_replace('/', '\\', $file),
                        'stub' => $stub
                    ];
                } else {
                    $arrayFiles[$key] = [
                        'name' => $action,
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
}
