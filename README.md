# Drupal Pyramid Theme

This theme is used in our main project at [Drupal Pyramid](https://github.com/drupal-pyramid/drupal-pyramid).

It aims to be added via Composer and be updated via Git Subtree.

## Getting started

1. Add this theme as a dependency to your main Drupal project:
```
composer require drupal-pyramid/drupal_pyramid_theme
```

2. **Add autoload custom scripts to your composer.json**:

```
  "scripts": {
    "...",
    "drupal-pyramid-theme-build": "DrupalPyramidTheme\\composer\\ScriptHandler::build",
    "drupal-pyramid-theme-update": "DrupalPyramidTheme\\composer\\ScriptHandler::update",
    "post-install-cmd": [
      "...",
      "@drupal-pyramid-theme-build"
    ],
    "post-update-cmd": [
      "...",
      "@drupal-pyramid-theme-update"
    ]
  }
```

> If you want to know why you have to add the autoload scripts yourself, see [this thread](https://github.com/composer/composer/issues/1193).


3. Add this theme as a subproject with Git Subtree:
```
git remote add -f drupal_pyramid_theme ssh://git@github.com:drupal-pyramid/drupal_pyramid_theme.git
git subtree add --prefix web/modules/custom/drupal_pyramid_theme drupal_pyramid_theme master --squash
git fetch drupal_pyramid_theme master
git subtree pull --prefix web/modules/custom/drupal_pyramid_theme drupal_pyramid_theme master --squash
```

4. Contribute back to this theme:
```
git add .
git commit -m "Your commit message"
git subtree push --prefix=web/modules/custom/drupal_pyramid_theme drupal_pyramid_theme master
```

## Build and assets

We use custom Composer scripts to generate assets (see `composer.json`).

```
  "scripts": {
    "drupal-pyramid-theme-build": "DrupalPyramidTheme\\ScriptHandler::build",
    "drupal-pyramid-theme-update": "DrupalPyramidTheme\\ScriptHandler::update",
    "post-install-cmd": [
      "@drupal-pyramid-theme-build"
    ],
    "post-update-cmd": [
      "@drupal-pyramid-theme-update"
    ]
  }
```
