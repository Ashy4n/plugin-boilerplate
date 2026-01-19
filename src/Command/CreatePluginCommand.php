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

        $questions = [
            'plugin_name' => new Question('Plugin Name: '),
            'namespace'   => new Question('Namespace: '),
            'description' => new Question('Description: ', 'A professional WP plugin'),
            'author'      => new Question('Author: ', 'Your Company'),
            'php_version' => new Question('PHP Version: ', '8.1'),
        ];

        $answers = [];
        foreach ($questions as $key => $question) {
            $answers[$key] = $helper->ask($input, $output, $question);
        }

        $answers['slug'] = strtolower(str_replace(' ', '-', $answers['plugin_name']));
        $answers['vendor_slug'] = 'company-x';
        $answers['namespace_escaped'] = str_replace('\\', '\\\\', $answers['namespace']);

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

        if (!is_dir("{$targetDir}/src")) mkdir("{$targetDir}/src", 0755, true);
        if (!is_dir("{$targetDir}/tests")) mkdir("{$targetDir}/tests", 0755, true);
    }
}
