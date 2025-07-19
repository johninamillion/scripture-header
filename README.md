# Scripture Header

[![PHPUnit](https://github.com/johninamillion/scripture-header/actions/workflows/phpunit.yml/badge.svg)](https://github.com/johninamillion/scripture-header/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/johninamillion/scripture-header/actions/workflows/phpstan.yml/badge.svg)](https://github.com/johninamillion/scripture-header/actions/workflows/phpstan.yml)
[![PHP-CS-Fixer](https://github.com/johninamillion/scripture-header/actions/workflows/phpcsfixer.yml/badge.svg)](https://github.com/johninamillion/scripture-header/actions/workflows/phpcsfixer.yml)

Scripture Header is a PHP package that allows you to add copyright headers with bible verses to your code files as comments via [php-cs-fixer/php-cs-fixer](https://github.com/php-cs-fixer/php-cs-fixer). 
It supports various Bible translations from [scrollmapper/bible_databases](https://github.com/scrollmapper/bible_databases) and can be easily integrated into your development workflow.

---

## Table of Contents

- [Installation](#installation)
- [Customization](#customization)
- [Development](#development)
- [License](#license)

---

## Installation

You can install the package via Composer:

```bash
composer require johninamillion/scripture-header
```

### Basic Usage
To use Scripture Header, you need to configure PHP-CS-Fixer to apply the header to your files. 
Create or update the `.php-cs-fixer.php.dist` file in the root of your project with the following configuration:

#### Example Configuration

```php
<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use johninamillion\ScriptureHeader\ScriptureHeaderFixer;

// Include the ScriptureHeaderFixer class for github actions.
if (!class_exists(ScriptureHeaderFixer::class)) {
    require_once __DIR__ . '/src/ScriptureHeaderFixer.php';
}

$finder = Finder::create()
    ->in(__DIR__ . '/src');

return (new Config())
    ->setRules([
        'MillionVisions/scripture_header' => true,
    ])
    ->setFinder($finder)
    ->registerCustomFixers([
        'MillionVisions/scripture_header' => new ScriptureHeaderFixer()
    ]);
```

## Customization

You can easily customize the rules for the Scripture Header Fixer by passing options.

```php
    ...
    ->setRules([
        'MillionVisions/scripture_header' => [
            'author'    => 'Your Name',
            'bible'     => 'data/BIBLE.json',
            'template'  => 'your-copyright.php'
        ]
    ])
    ... 
```

| Option       | Description                                                                                                                                                                                                                                                 | Default Value                    |
|--------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------|
| **author**   | Custom author name.                                                                                                                                                                                                                                         | Vendor name from `composer.json` |
| **bible**    | Custom path to any bible translation in json format from [scrollmapper/bible_databases](https://github.com/scrollmapper/bible_databases). <br> **This package only provides the `KJV.json`.** <br> **The filename is used as suffix for the bible verses.** | `data/KJV.json`                  |
| **template** | Custom template for your copyright header.                                                                                                                                                                                                                  | `./copyright.php`                |


## Development

### Analyze

To analyze your code for potential issues, you can run [phpstan](https://github.com/phpstan/phpstan):

```bash
composer phpstan
```

### Testing

To run the tests, make sure you have installed [phpunit](https://github.com/sebastianbergmann/phpunit) within the dev dependencies and then run:

```bash
composer test
```

### CS-Fixer

To ensure your code adheres to the coding standards, you can run the [php-cs-fixer](https://github.com/php-cs-fixer/php-cs-fixer).

```bash
composer csfix
```

---

## License
This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

---

<div style="text-align: center">All Glory To God - The Father, The Son, and The Holy Spirit.</div><br>
