# Summary

This package contains a plugin for composer. 
The plugin detects optimisations for the `composer.json` file of a project.
The plugin hooks in on existing composer commands.

# Installation

The package can be installed with:

```
composer require mediact/composer-unclog-plugin -dev
```

# Configuration

The plugin can be configured to allow more repository types. 
This is possible by adding a `add-allowed-repositories` array node to the 
`config` node of the project.
The configuration will look like:
```
...
    "config": {
        "add-allowed-repositories": [
            "git"
        ],
        "sort-packages": true
    },
...
```

The repository types are then added to the array of allowed repository types in 
the plugin.

# Usage

By running one of the following commands, 
the plugin will perform additional checks for the `composer.json` file.

```
composer validate
composer install
composer update
```

# Current checks

- Check if all repositories are of type `composer`.
- Check if all packages have version numbers instead of `dev-` packages.

# Future ideas

- Add commands to suggestions to fix the problem (`composer optimize --repositories`).
- Detect if the package is available on a repository and do a suggestion.
- Detect which packages are being used from a non composer repository
and check if it can be installed through another configured repository.
