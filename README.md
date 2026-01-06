# WordPress Plugin Generator

A high-performance CLI scaffolding tool built for **Company X** to standardize WordPress plugin development. This tool ensures every new project starts with a robust architecture, strict type-checking, and automated quality assurance tools.

## 🚀 Features

-   **Modern PHP:** Generated code targets PHP 8.1+ features (Readonly properties, Union types, etc.).
-   **Architectural Standard:** Implements the **Singleton pattern** for the main plugin class to prevent hook collisions.
-   **PSR-4 Autoloading:** Ready-to-use directory structure with `src/` and `tests/`.
-   **QA-Ready:** Automatic configuration of:
    -   **PHPStan:** Static analysis (Level 6 by default) with WordPress extensions.
    -   **PHP_CodeSniffer:** Enforcing WordPress Coding Standards (WPCS).
    -   **PHPUnit:** Unit testing suite ready for `tests/`.
-   **DevOps Friendly:** Automates `git init` and `composer install` upon creation.

---

## 🛠 Prerequisites

-   **PHP:** 8.1 or higher
-   **Composer:** Latest version
-   **Git:** Installed and configured in your PATH

---

## 📥 Installation

1.  **Clone the generator repository:**
    ```bash
    git clone https://github.com/company-x/wp-generator.git
    cd wp-generator
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    ```

3.  **Make the binary executable:**
    ```bash
    chmod +x bin/gen-plugin
    ```

4.  **(Optional) Create a global symlink:**
    To run the generator from anywhere, link it to your local bin:
    ```bash
    ln -s $(pwd)/bin/gen-plugin /usr/local/bin/gen-plugin
    ```

---

## 🕹 Usage

Navigate to your WordPress plugins directory (`wp-content/plugins`) and run:

```bash
gen-plugin create-plugin
```

### Interactive Prompts
The tool will guide you through the setup:
1.  **Plugin Name:** (e.g., `Custom Analytics`)
2.  **Namespace:** (e.g., `CompanyX\Analytics`)
3.  **Description:** (Short summary of the plugin)
4.  **Author:** (Your name or team name)
5.  **PHP Version:** (Defaults to `8.1`)

---

## 🏗 Generated Project Structure

The generator produces the following clean architecture:

```text
/my-plugin
|-- /src                # PSR-4 logic (Classes here)
|-- /tests              # Unit and Integration tests
|-- /vendor             # Composer dependencies (Auto-generated)
|-- .gitignore          # Pre-configured for WP/PHP
|-- composer.json       # Script definitions (lint, analyse, test)
|-- my-plugin.php       # Main Entry Point (Singleton)
|-- phpcs.xml           # WP Coding Standards config
|-- phpstan.neon        # Static analysis config
|-- phpunit.xml         # Test suite config
|-- uninstall.php       # Safe cleanup script
```

---

## 🧪 Developer Workflow in Generated Plugins

Once your plugin is generated, the following commands are available inside the new plugin folder:

-   **Check Coding Standards:**
    ```bash
    composer lint
    ```
-   **Auto-fix Formatting:**
    ```bash
    composer format
    ```
-   **Run Static Analysis:**
    ```bash
    composer analyse
    ```
-   **Run Tests:**
    ```bash
    composer test
    ```

---

## ⚙️ Architecture Notes

### The Singleton Pattern
The main plugin class in `{{plugin-slug}}.php` is generated as a `final class` with a `private __construct`. This ensures:
-   Global access via `Plugin::getInstance()`.
-   Protection against accidental multiple instances.
-   Proper lifecycle management for WordPress hooks.

### Strict Typing
Every stub uses `declare(strict_types=1);` where applicable and leverages PHP 8.1 type hinting to reduce runtime errors and improve IDE autocompletion.

---
