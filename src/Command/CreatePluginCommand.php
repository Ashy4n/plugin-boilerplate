<?php

namespace Ashyan\PluginBoilerplate\Command;

use Ashyan\PluginBoilerplate\Service\FileGeneratorService;
use Ashyan\PluginBoilerplate\Service\ProcessRunnerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

// Atrybut definiuje nazwę i opis, eliminując błąd "empty name"
#[AsCommand(
    name: 'create-plugin',
    description: 'Scaffolds a professional WordPress plugin structure.'
)]
class CreatePluginCommand extends Command {

    public function __construct(
        private FileGeneratorService $fileGenerator,
        private ProcessRunnerService $processRunner
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $io->title('Company X: WordPress Plugin Generator');

        $data = $this->collectData($input, $output, $io);
        $targetDir = getcwd() . '/' . $data['slug'];

        if (is_dir($targetDir)) {
            $io->error("Directory {$data['slug']} already exists!");
            return Command::FAILURE;
        }

        $io->section('Generating file structure...');

        $this->generateFiles($targetDir, $data);

        $io->section('Initializing Environment...');
        $this->processRunner->run(['git', 'init'], $targetDir, $io);

        $io->note('Installing dependencies via Composer...');
        $this->processRunner->run(['composer', 'install'], $targetDir, $io);

        $io->success("Plugin '{$data['plugin_name']}' created successfully!");

        return Command::SUCCESS;
    }

    private function collectData(InputInterface $input, OutputInterface $output, SymfonyStyle $io): array {
        $helper = $this->getHelper('question');
        $history = $this->loadHistory();
        $last = $history[0] ?? [];

        $answers = [];

        $answers['plugin_name'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Plugin Name: ',
                $this->getLastValue($last, 'plugin_name'),
                null,
                $this->buildAutocompleteValues($history, 'plugin_name')
            )
        );
        $answers['slug'] = strtolower(str_replace(' ', '-', $answers['plugin_name']));

        $namespaceDefault = $this->deriveNamespace($answers['plugin_name']);
        $answers['namespace'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Namespace: ',
                $namespaceDefault,
                null,
                $this->formatAutocompleteValues([$namespaceDefault])
            )
        );
        $answers['description'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Description: ',
                $this->getLastValue($last, 'description', 'A professional WP plugin'),
                null,
                $this->buildAutocompleteValues($history, 'description')
            )
        );
        $answers['author'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Author: ',
                $this->getLastValue($last, 'author', 'Your Company'),
                null,
                $this->buildAutocompleteValues($history, 'author')
            )
        );
        $answers['email'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Author Email: ',
                $this->getLastValue($last, 'email'),
                [$this, 'validateEmail'],
                $this->buildAutocompleteValues($history, 'email')
            )
        );
        $answers['author_url'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Author URL: ',
                $this->getLastValue($last, 'author_url'),
                fn(string $value) => $this->validateUrl($value, 'Author URL')
                ,
                $this->buildAutocompleteValues($history, 'author_url')
            )
        );
        $answers['plugin_uri'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Plugin URL: ',
                $this->getLastValue($last, 'plugin_uri'),
                fn(string $value) => $this->validateUrl($value, 'Plugin URL')
                ,
                $this->buildAutocompleteValues($history, 'plugin_uri')
            )
        );
        $answers['text_domain'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Text Domain: ',
                $this->getLastValue($last, 'text_domain', $answers['slug']),
                [$this, 'validateTextDomain'],
                $this->buildAutocompleteValues($history, 'text_domain')
            )
        );
        $answers['min_php'] = $helper->ask(
            $input,
            $output,
            $this->createRequiredQuestion(
                'Minimum PHP Version: ',
                $this->getLastValue($last, 'min_php', '8.1'),
                [$this, 'validatePhpVersion'],
                $this->buildAutocompleteValues($history, 'min_php')
            )
        );

        $answers['vendor_slug'] = 'company-x';
        $answers['namespace_escaped'] = str_replace('\\', '\\\\', $answers['namespace']);

        $this->storeHistory($history, $answers);

        return $answers;
    }

    private function generateFiles(string $targetDir, array $data): void {
        $files = [
            'plugin-main'   => "{$targetDir}/{$data['slug']}.php",
            'composer.json' => "{$targetDir}/composer.json",
            'phpstan.neon'  => "{$targetDir}/phpstan.neon",
            'phpcs.xml'     => "{$targetDir}/phpcs.xml",
            'phpunit.xml'   => "{$targetDir}/phpunit.xml",
            'gitignore'     => "{$targetDir}/.gitignore",
            'uninstall.php' => "{$targetDir}/uninstall.php",
        ];

        foreach ($files as $stub => $dest) {
            $this->fileGenerator->generate($stub, $dest, $data);
        }

	    if ( ! is_dir( "{$targetDir}/src" ) ) {
		    mkdir( "{$targetDir}/src", 0755, true );
	    }
	    if ( ! is_dir( "{$targetDir}/tests" ) ) {
		    mkdir( "{$targetDir}/tests", 0755, true );
	    }
	    if ( ! is_dir( "{$targetDir}/vendor_prefixed" ) ) {
		    mkdir( "{$targetDir}/vendor_prefixed", 0755, true );
	    }
    }

    private function createRequiredQuestion(
        string $prompt,
        ?string $default = null,
        ?callable $validator = null,
        array $autocompleteValues = []
    ): Question {
        $question = new Question($prompt, $default);
        if ($autocompleteValues !== []) {
            $question->setAutocompleterValues($autocompleteValues);
        }
        $question->setValidator(function ($value) use ($validator, $default) {
            $value = trim(strip_tags((string)$value));
            if ($value === '') {
                if ($default !== null) {
                    $value = (string)$default;
                } else {
                    throw new \RuntimeException('This value is required.');
                }
            }
            if ($validator !== null) {
                $value = $validator($value);
            }
            return $value;
        });

        return $question;
    }

    private function validateEmail(string $value): string {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Please provide a valid email address.');
        }

        return $value;
    }

    private function validateUrl(string $value, string $label): string {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException("Please provide a valid {$label}.");
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException("{$label} must start with http:// or https://.");
        }

        return $value;
    }

    private function validateTextDomain(string $value): string {
        $value = strtolower($value);
        if (!preg_match('/^[a-z0-9_-]+$/', $value)) {
            throw new \RuntimeException('Text domain may contain only lowercase letters, numbers, hyphens, and underscores.');
        }

        return $value;
    }

    private function validatePhpVersion(string $value): string {
        if (!preg_match('/^\\d+\\.\\d+(\\.\\d+)?$/', $value)) {
            throw new \RuntimeException('Minimum PHP version must look like 8.1 or 8.1.0.');
        }

        return $value;
    }

    private function deriveNamespace(string $pluginName): string {
        $clean = preg_replace('/[^A-Za-z0-9]+/', ' ', $pluginName);
        $parts = array_filter(explode(' ', trim((string)$clean)));
        $studly = '';
        foreach ($parts as $part) {
            $studly .= ucfirst(strtolower($part));
        }

        return $studly !== '' ? $studly : 'Plugin';
    }

    private function loadHistory(): array {
        $path = $this->getHistoryPath();
        if (!file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }

        $data = json_decode($contents, true);
        return is_array($data) ? $data : [];
    }

    private function storeHistory(array $history, array $answers): void {
        $record = [
            'plugin_name' => $answers['plugin_name'] ?? '',
            'namespace' => $answers['namespace'] ?? '',
            'description' => $answers['description'] ?? '',
            'author' => $answers['author'] ?? '',
            'email' => $answers['email'] ?? '',
            'author_url' => $answers['author_url'] ?? '',
            'plugin_uri' => $answers['plugin_uri'] ?? '',
            'text_domain' => $answers['text_domain'] ?? '',
            'min_php' => $answers['min_php'] ?? '',
        ];

        array_unshift($history, $record);
        $history = array_slice($history, 0, 10);

        $path = $this->getHistoryPath();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("History directory could not be created: {$dir}");
        }

        file_put_contents($path, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function getHistoryPath(): string {
        return dirname(__DIR__, 2) . '/data/inputs.json';
    }

    private function getLastValue(array $last, string $key, ?string $fallback = null): ?string {
        $value = $last[$key] ?? null;
        return $value !== null && $value !== '' ? (string)$value : $fallback;
    }

    private function buildAutocompleteValues(array $history, string $key): array {
        $values = [];
        foreach ($history as $entry) {
            if (!isset($entry[$key]) || $entry[$key] === '') {
                continue;
            }
            $values[] = (string)$entry[$key];
        }

        return $this->formatAutocompleteValues(array_values(array_unique($values)));
    }

    private function formatAutocompleteValues(array $values): array {
        $formatted = [];
        foreach ($values as $value) {
            $formatted[] = "<comment>{$value}</comment>";
        }

        return $formatted;
    }
}
