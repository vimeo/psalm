# Installation

The latest version of Psalm requires PHP >= 7.4 and [Composer](https://getcomposer.org/).

```bash
composer require --dev vimeo/psalm
```

Generate a config file:

```bash
./vendor/bin/psalm --init
```

Psalm will scan your project and figure out an appropriate [error level](error_levels.md) for your codebase.

Then run Psalm:

```bash
./vendor/bin/psalm
```

Psalm will probably find a number of issues - find out how to deal with them in [Dealing with code issues](dealing_with_code_issues.md).

## Installing plugins

While Psalm can figure out the types used by various libraries based on
their source code and docblocks, it works even better with custom-tailored types
provided by Psalm plugins.

Check out the [list of existing plugins on Packagist](https://packagist.org/?type=psalm-plugin).
Install them with `composer require --dev <plugin/package> && vendor/bin/psalm-plugin enable <plugin/package>`

Read more about plugins in [Using Plugins chapter](plugins/using_plugins.md).

## Using the Phar

Sometimes your project can conflict with one or more of Psalm’s dependencies. In
that case you may find the Phar (a self-contained PHP executable) useful.

The Phar can be downloaded from Github:

```bash
wget https://github.com/vimeo/psalm/releases/latest/download/psalm.phar
chmod +x psalm.phar
./psalm.phar --version
```

Alternatively, you can use Composer to install the Phar:

```bash
composer require --dev psalm/phar
```
# Alternative Installation Methods

The following methods are known to work for some users, but are **not** officially supported.

## Installing with `composer` globally

```bash
composer global require vimeo/psalm
```

## Installing with `phive`

```bash
phive install psalm
```

## Installing Psalm on Linux using a package manager

Psalm is available across various Linux distributions, each typically equipped
with its own package management system. To install Psalm, refer to the
documentation specific to your distribution's package manager.

### Installation on Nix

For users of Nix, Psalm is readily [available](https://search.nixos.org/packages?channel=23.11&from=0&size=50&sort=relevance&type=packages&query=psalm)
and can be installed or run using several methods.

To run Psalm directly, use the following command:

```bash
nix run github:NixOS/nixpkgs-unstable#php.packages.psalm
```

For an ephemeral shell environment with Psalm, either of the following commands
can be used:

```bash
nix-shell -p php.packages.psalm
```

```bash
nix shell github:NixOS/nixpkgs-unstable#php.packages.psalm
```

Psalm builds on Nix are reproducible, ensuring that executing the same command
on any system yields an identical build of Psalm.
