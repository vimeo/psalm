## Using a Snapshot Build

To obtain the latest development version of Psalm, a snapshot build can be
downloaded from GitHub at https://github.com/vimeo/psalm/archive/refs/heads/5.x.zip

Snapshot builds typically include only the source code and exclude the `.git`
directory. As a result, Psalm cannot automatically determine its version or
revision when executing `composer install`, since this information is usually
derived from the `git` repository metadata that Composer relies on.

To successfully install Psalm from a snapshot build, it is necessary to
manually specify the version using the `COMPOSER_ROOT_VERSION` environment
variable. This allows Composer to proceed with the installation of Psalm.

The command to do so is as follows:

```bash
COMPOSER_ROOT_VERSION=5.x-dev composer install
```
