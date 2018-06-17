# Drupal Pyramid

A starter kit for your Drupal projects with instructions to work on multiple subprojects.


## Getting started

All you need is [Composer](https://getcomposer.org/doc/00-intro.md) and [Lando](https://docs.devwithlando.io/installation/installing.html).

```
composer create-project drupal-pyramid/drupal-pyramid <project_name> --stability dev --no-interaction
cp example.lando.yml .lando
nano .lando.yml # Edit first line to rename the project (e.g pyramid)
lando start
```

You now have working Drupal website at [https://<project_name>.lndo.site](https://<project_name>.lndo.site)


![Small. Simple. Fast.](https://media.giphy.com/media/iqryv05RJMVlm/giphy.gif)


---

Any questions? Please have a look at [the wiki](https://github.com/drupal-pyramid/drupal-pyramid/wiki) or [open a new issue](https://github.com/drupal-pyramid/drupal-pyramid/issues).
