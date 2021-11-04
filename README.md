# Watcher CRM

[![Total Downloads](https://img.shields.io/packagist/dt/mapik/watcher-crm.svg?style=flat-square)](https://packagist.org/packages/mapik/watcher-crm)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

The framework source code can be found here: [cakephp/cakephp](https://github.com/cakephp/cakephp).

## Description
- Customer Relationship Management System for evidence of customers for ISP
- it can generate invoices in dBase format for POHODA Stormware bookkeeping software

## Installation

1. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `php composer.phar create-project --prefer-dist mapik/watcher-crm [app_name]`.

If Composer is installed globally, run

```bash
composer create-project --prefer-dist mapik/watcher-crm
```

In case you want to use a custom app dir name (e.g. `/myapp/`):

```bash
composer create-project --prefer-dist mapik/watcher-crm myapp
```

You can now either use your machine's webserver to view the default home page, or start
up the built-in webserver with:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Configuration

Create and edit the `config/.env` or set system environment variables (eg. for Docker).

## Layout

The app uses [Milligram](https://milligram.io/) (v1.3) minimalist CSS
framework.
