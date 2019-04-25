# Pyramid Content module

Example module to import/export default content from/to your Drupal website.

## How to install

Enable and disable the custom module which holds our default content:

```
lando drush en pyramid_content -y
lando drush pmu pyramid_content -y
```

After import, disable Core modules that were required by `pyramid_content` if you don't use them:

```
lando drush pmu default_content default_content_extra hal serialization -y
```

## Export default content

```
lando drush en default_content_extra -y
lando drush dcer menu_link_content --folder=modules/custom/pyramid_content/content -y
lando drush dcer node --folder=modules/custom/pyramid_content/content -y
lando drush dcer media --folder=modules/custom/pyramid_content/content -y
lando drush dcer paragraph --folder=modules/custom/pyramid_content/content -y
lando drush dcer taxonomy_term --folder=modules/custom/pyramid_content/content -y
lando drush dcer user --folder=modules/custom/pyramid_content/content -y
```

We recommend you to remove the `user` folder to avoid conflicts with `duplicated user` during next import.

```
rm -rf web/modules/custom/pyramid_content/content/user
```

After import, disable Core modules that were required by `pyramid_content` if you don't use them:

```
lando drush pmu default_content default_content_extra hal serialization -y
```
