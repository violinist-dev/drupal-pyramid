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

--- 

## Known issues

**Build fails and permission denied**
If for some reason the build process does not end up correctly, you might have permission issues with the `sites/default` folder.

```bash
[ErrorException] file_put_contents(/app/web/sites/default/settings.php): failed to open stream: Permission denied
```

```bash
sh: 1: node_modules/.bin/node-sass: Permission denied
npm info lifecycle drupal-pyramid@1.0.0~build:css: Failed to exec build:css script
npm ERR! Linux 4.15.0-22-generic
npm ERR! argv "/usr/local/bin/node" "/usr/local/bin/npm" "run" "build:css"
npm ERR! node v6.10.3
npm ERR! npm  v3.10.10
npm ERR! code ELIFECYCLE
npm ERR! drupal-pyramid@1.0.0 build:css: `cd $npm_package_config_theme_path && node_modules/.bin/node-sass scss scss/ -o css/`
npm ERR! Exit status 126
npm ERR!
npm ERR! Failed at the drupal-pyramid@1.0.0 build:css script 'cd $npm_package_config_theme_path && node_modules/.bin/node-sass scss scss/ -o css/'.
```

**Solution**

Fix files and folder permissions by running these helpers scripts:

```bash
lando composer fix-permissions
lando npm run reset
```


---


Any questions? Please have a look at [the wiki](https://github.com/drupal-pyramid/drupal-pyramid/wiki) or [open a new issue](https://github.com/drupal-pyramid/drupal-pyramid/issues).
