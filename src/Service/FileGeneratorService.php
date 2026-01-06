<?php

namespace Ashyan\PluginBoilerplate\Service;

class FileGeneratorService {
    private string $stubsPath;

    public function __construct(string $stubsPath) {
        $this->stubsPath = rtrim($stubsPath, '/');
    }

    public function generate(string $templateName, string $destination, array $placeholders): void {
        $stubPath = "{$this->stubsPath}/{$templateName}.stub";

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub template not found: {$templateName}");
        }

        $content = file_get_contents($stubPath);

        foreach ($placeholders as $key => $value) {
            $content = str_replace("{{{$key}}}", (string)$value, $content);
        }

        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($destination, $content);
    }
}
