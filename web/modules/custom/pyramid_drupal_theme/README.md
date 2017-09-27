# Pyramid Drupal Theme

This file has been created from within our main project [Pyramid](https://bitbucket.org/appno/pyramid-drupal).

If you see it, it means you have successfully configured Git subtree.

Congrats!

## How to add this theme with Git subtree

```
git remote add -f pyramid_drupal_theme ssh://git@bitbucket.org/appno/pyramid_drupal_theme.git
git subtree add --prefix web/modules/custom/pyramid_drupal_theme pyramid_drupal_theme master --squash
git fetch pyramid_drupal_theme master
git subtree pull --prefix web/modules/custom/pyramid_drupal_theme pyramid_drupal_theme master --squash
git subtree push --prefix=web/modules/custom/pyramid_drupal_theme pyramid_drupal_theme master
```