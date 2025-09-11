# Composer Autoload Files From Directories Plugin

[![License](https://img.shields.io/packagist/l/everton3x/autoload-files-plugin.svg)](https://packagist.org/packages/everton3x/autoload-files-plugin)
[![PHP Version](https://img.shields.io/packagist/php-v/everton3x/autoload-files-plugin)](https://php.net)

A Composer plugin to automatically load **all PHP files** from specified directories into the `autoload.files` configuration. Perfect for projects with helper files, legacy code, or configuration scripts.

---

## ✨ Features

- **Auto-include PHP files**: Automatically adds all `.php` files from configured directories to Composer's autoload.
- **Directory scanning**: Supports nested directories (recursive scanning).
- **Symfony Finder integration**: Efficient file discovery with the Symfony Finder component.
- **Composer event-driven**: Hooks into Composer's `pre-autoload-dump` event.

---

## 🚀 Installation

Install via Composer:

```bash
composer require everton3x/autoload-files-plugin`
```

## 🛠️ Usage
1. Configure Directories

Add your target directories to composer.json under the extra section:
```json
{
    "extra": {
        "autoload-files-from": [
            "src/Helpers",
            "config/scripts",
            "legacy"
        ]
    }
}
```

2. Run Composer

Update and regenerate the autoloader:

```bash
composer update
composer dump-autoload
```

All PHP files in the specified directories will now be autoloaded!

## 🔍 How It Works
The plugin:

Listens to Composer's pre-autoload-dump event

Scans all directories listed in extra.autoload-files-from

Adds every found .php file to autoload.files

Updates Composer's autoload configuration

**📂 Example Structure**

```
my-project/
├── src/
│   └── Helpers/
│       ├── StringHelper.php
│       └── ArrayHelper.php
├── config/
│   └── scripts/
│       └── bootstrap.php
```

**Result:**

```php
// vendor/composer/autoload_files.php
return [
    // ...
    'a1b2c3' => $baseDir . '/src/Helpers/StringHelper.php',
    'd4e5f6' => $baseDir . '/src/Helpers/ArrayHelper.php',
    'g7h8i9' => $baseDir . '/config/scripts/bootstrap.php',
];
```

## ⚠️ Notes
Performance: Avoid very large directories (1000+ files) as this may slow down Composer operations.

Duplicates: Files already listed in autoload.files will not be added twice.

Alternatives: For class-based autoloading, prefer psr-4 or classmap.

## 🤝 Contributing
Fork the repository

Create a feature branch (git checkout -b feature/awesome-feature)

Commit changes (git commit -am 'Add awesome feature')

Push to branch (git push origin feature/awesome-feature)

Open a Pull Request

## 📄 License
MIT License. See [LICENSE](LICENSE) for details.

Happy autoloading! 🎉
Created with ❤️ for PHP developers