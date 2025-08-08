<?php

declare(strict_types=1);

namespace Ranierif\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeApiMockerCommand extends Command
{
    protected static string $defaultName = 'make:api-mocker';

    protected static string $defaultDescription = 'Create a new API Mocker class';

    private string $baseDir;

    private string $stubPath;

    public function __construct()
    {
        parent::__construct();

        $this->baseDir = $this->getProjectRoot();
        $this->stubPath = __DIR__ . '/stubs/api-mocker.stub';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $name = $input->getArgument('name');

            if (empty($name) || ! is_string($name)) {
                throw new \InvalidArgumentException('Provider name is required');
            }

            $providerName = ucfirst($name);

            $className = "{$name}ApiMocker";

            $this->createDirectories($providerName);
            $this->createMockerFile($className, $providerName);

            $output->writeln(sprintf(
                '<info>ApiMocker created successfully: tests/ApiMocker/%s/%s.php</info>',
                $providerName,
                $className
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'The name of the API provider (e.g., Stripe, PayPal)'
        );
    }

    private function getProjectRoot(): string
    {
        $cwd = getcwd() ?: '';
        $cwdReal = $cwd !== '' ? (realpath($cwd) ?: $cwd) : '';

        if ($cwd !== '' && file_exists($cwd . '/composer.json')) {
            return $cwdReal;
        }

        $autoloadPaths = [
            __DIR__ . '/../../../../autoload.php',
            __DIR__ . '/../../../autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ];

        foreach ($autoloadPaths as $path) {
            if (file_exists($path)) {
                $root = dirname($path, 2);
                return realpath($root) ?: $root;
            }
        }

        return $cwdReal;
    }

    private function createDirectories(string $providerName): void
    {
        $directories = [
            $this->baseDir . '/tests',
            $this->baseDir . '/tests/ApiMocker',
            $this->baseDir . '/tests/ApiMocker/' . $providerName,
            $this->baseDir . '/tests/ApiMocker/' . $providerName . '/json',
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    private function createMockerFile(string $className, string $providerName): void
    {
        $stub = file_get_contents($this->stubPath) ?: '';
        $content = str_replace(
            ['{{className}}', '{{providerName}}'],
            [$className, $providerName],
            $stub
        );

        $filePath = "{$this->baseDir}/tests/ApiMocker/{$providerName}/{$className}.php";
        file_put_contents($filePath, $content);
    }
}
