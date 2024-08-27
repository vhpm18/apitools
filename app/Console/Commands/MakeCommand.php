<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Version;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

final class MakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:make
                            {name : The resource name. E.g. Posts}
                            {namespace : The namespace name. E.g. V1/Post}
                            {--ver=v1.0 : The version used to create sub-folders}
                            {--C|controllers : Create only the controller files}
                            {--c|no-controllers : Create all files except the controller files}
                            {--T|request : Create only the request files}
                            {--t|no-request : Create all files except the request files}
                            {--R|resource : Create only the resource files}
                            {--r|no-resource : Create all files except the resource files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all the needed files for the given resource';

    protected function getStub() {}

    public function handle(): int
    {
        /** @var string $name */
        $name = $this->argument('name');

        /** @var string $namespace */
        $namespace = $this->argument('namespace');

        /** @var string $version */
        $version = $this->option('ver');

        $resource = (string) Str::of($name)->studly()->plural();
        $version  = Version::from($version)->name;

        if ((bool) $this->option('controllers')) {
            $this->makeControllers($resource, $version, $namespace);
            $this->makeService(Str::singular($resource), $version, $namespace);

            return 0;
        }

        if ((bool) $this->option('request')) {
            $this->makeRequests($resource, $version, $namespace);

            return 0;
        }

        if ((bool) $this->option('resource')) {
            $this->makeResources($resource, $version, $namespace);

            return 0;
        }

        if (! (bool) $this->option('no-controllers')) {
            $this->makeControllers($resource, $version, $namespace);
            $this->makeService(Str::singular($resource), $version, $namespace);
        }

        if (! (bool) $this->option('no-request')) {
            $this->makeRequests($resource, $version, $namespace);
        }

        if (! (bool) $this->option('no-resource')) {
            $this->makeResources($resource, $version, $namespace);
        }

        $this->makeTests($resource, $version, $namespace);

        return 0;
    }

    private function makeControllers(string $resource, string $version, string $namespace): void
    {
        collect(['Destroy', 'Index', 'Show', 'Store', 'Update'])
            ->each(
                function (string $action) use ($resource,  $version, $namespace) {
                    $this->callSilently('make:controller', [
                        'name'    => $this->buildFilePath('controller', $resource, $version, $action, $namespace),
                        '--type'  => Str::lower($action),
                        '--model' => $this->guessModelFromResourceName($resource),
                    ]);
                }
            );
    }

    private function makeService(string $resource, string $version, string $namespace): void
    {
        $this->callSilently('make:service', [
            'name' => $this->buildFilePath(
                'service',
                $resource,
                $version,
                null,
                $namespace
            ),
            '--model' => $resource,
        ]);
    }

    private function makeRequests(string $resource, string $version, string $namespace): void
    {
        collect(['Store', 'Update'])
            ->each(fn(string $action): int => $this->callSilently('make:request', [
                'name' => $this->buildFilePath('request', $resource, $version, $action, $namespace),
            ]));
    }

    private function makeResources(string $resource, string $version, string $namespace): void
    {
        $this->callSilently('make:resource', [
            'name' => $this->buildFilePath('resource', $resource, $version, null, $namespace),
        ]);
    }

    private function makeTests(string $resource, string $version, string $namespace): void
    {
        collect(['Destroy', 'Index', 'Show', 'Store', 'Update'])
            ->each(fn(string $action): int => $this->callSilently('make:test', [
                'name'   => $this->buildFilePath('test', $resource, $version, $action, $namespace),
                '--pest' => 1,
            ]));
    }

    private function buildFilePath(
        string $type,
        string $resource,
        string $version,
        string $action = null,
        string $namespace
    ): string {
        return match ($type) {
            'controller' => sprintf('Api/%s/%s/%s%sController', $namespace, $resource, $resource, $action),
            'service' => sprintf('/%s/%sService', $namespace, $resource),
            'request'    => sprintf('Api/%s/%s%sRequest', $namespace, Str::singular($resource), $action),
            'resource'   => sprintf('Api/%s/%sResource', $namespace,  Str::singular($resource)),
            default      => sprintf('Http/Controllers/Api/%s/%s/%s/%s%sControllerTest', $namespace, $version, $resource, $resource, $action),
        };
    }

    private function guessModelFromResourceName(string $resource): ?string
    {
        /** @var class-string $model */
        $model = 'App\\Models\\' . Str::of($resource)->studly()->singular();

        try {
            $reflector = new ReflectionClass($model);
            $filename  = $reflector->getFileName();
        } catch (ReflectionException) {
            return null;
        }

        if ($filename === false) {
            return null;
        }

        if (! file_exists($filename)) {
            return null;
        }

        return $model;
    }
}
