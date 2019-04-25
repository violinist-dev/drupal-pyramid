# Ignore config_split

## What is this?

This is a custom config_split folder. 

It is meant to hold specific configurations which should not be syncronized between environments.

This is why YAML files inside it are ignored by `.gitignore`.

It should ALWAYS be activated (e.g `/web/sites/example.settings.local.php`), as follow:

```php
$config_split_folders['ignore'] = TRUE; // Always activated.
```

## How to add configurations to this config_split?

Two ways:

* Prefix your config entity with `ignore_`
* Go to Admin > Config > Development > Config Split => Select a module and/or specific configurations in the blacklist section

Then, do a new export with `lando drush cex`.

Configuration files will be generated within this current folder.
