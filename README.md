# Drupal Pyramid

Small. Simple. Fast.

The best starter kit for your Drupal projects.

## Getting started

All you need is [Composer](https://getcomposer.org/doc/00-intro.md) and [Lando](https://docs.devwithlando.io/installation/installing.html).


**Create the project**:

```
composer create-project drupal-pyramid/drupal-pyramid <project_name> --stability dev --no-interaction
```

**Create the .lando.yml file**:

```
cp example.lando.yml .lando
```

Edit the first line in `.lando.yml` to rename your project (by default it's named _pyramid_):

```
name: <project_name>
recipe: drupal8
config:
  ...
```

**Start the machine**:

```bash
lando start
```

**Done!**

You now have a working Drupal website at [https://<project_name>.lndo.site](https://<project_name>.lndo.site).

See your local configuration with:

```bash
lando info
```

---

Any questions? Please have a look at [the wiki](https://github.com/drupal-pyramid/drupal-pyramid/wiki) or [open a new issue](https://github.com/drupal-pyramid/drupal-pyramid/issues).

---

![BrowserStack Status](https://www.browserstack.com/automate/badge.svg?badge_key=BvpY1y5yRRsxyUxUVDLJ)
