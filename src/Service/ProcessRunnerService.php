<?php

namespace Ashyan\PluginBoilerplate\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessRunnerService {
    public function run(array $command, string $cwd, SymfonyStyle $io): bool {
        $process = new Process($command, $cwd);
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        return $process->isSuccessful();
    }
}
