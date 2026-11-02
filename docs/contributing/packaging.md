# Packaging Psalm

The official ways to install Psalm are the Composer package, the `psalm.phar` download, and the phar installed through Composer (see [Installation](../running_psalm/installation.md)). This page is for packagers of other distributions (Nix, deb, rpm, AUR, ...); it is contributed by the community and not maintained by the Psalm team.

## Building from a source archive

A source archive (a GitHub snapshot, `git archive`, a tarball) has no `.git` directory, so Composer cannot work out the version of the root package and uses `1.0.0+no-version-set`. The `require-dev` plugins of Psalm require a recent `vimeo/psalm`, so the install fails, even with `--no-dev`:

```
$ composer install --no-dev
...
vimeo/psalm 1.0.0+no-version-set is the root package and cannot be modified.
```

Tell Composer the version with `COMPOSER_ROOT_VERSION`: the release you are packaging (a value lower than what the plugins require fails the same way).

```
$ COMPOSER_ROOT_VERSION=7.0.0 composer install --no-dev
$ ./psalm --version
Psalm 7.0.0@
```

Psalm's own CI does the same.
