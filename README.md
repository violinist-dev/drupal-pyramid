# Drupal Pyramid

A starter kit for your Drupal projects with instructions to work on multiple subprojects.

![Pyramids](http://drupal-pyramid.org/img/pyramids.jpg)


## Getting started

Create a new project with Composer:
```
composer create-project drupal-pyramid/drupal-pyramid <project_name> --stability dev --no-interaction
```

Alternatively, you can clone this repo and remove the Git history:
```
git clone git@github.com:drupal-pyramid/drupal-pyramid.git <project_name>
rm -rf <project_name>/.git
git init
git add .
git commit -m "Initial commit (Drupal Pyramid project)"
```

# How to install

We recommend you install [Lando]().

1. Init your environment
1. install dependencies
1. Install Drupal
1. Secure your installation
1. [Enjoy](http://gph.is/1auVl0T)!

```
lando init --recipe drupal8 --webroot web --name <project_name>
lando start
lando composer install
sudo cp web/sites/example.settings.local.php web/sites/default/settings.local.php
lando drush si config_installer -r web -y 
lando drush user-create yourname --password="yourpassword" --mail="your@email.com" -r web -y
lando drush user-add-role "administrator" --name=yourname -r web -y
lando drush user-password yourname --password=YourVeryLongAnd$ecureP@ssword -r web -y
lando drush user-block --name="admin" -r web -y
```

You now have working Drupal website at [https://<project_name>.lndo.site](https://<project_name>.lndo.site)


# How to add many repos

## Download subproject 

Preferably, you would host your subproject in a [Packagist](https://packagist.org/) server so you can download if as a dependency with Composer.
```
composer require <namespace>/<subproject_name>:~1.0
```

Alternatively, you can clone it from a Git repository.
```
git clone git@github.com:<namespace>/<subproject_name>.git custom/modules/<subproject_name>
```

## Add a git subtree

1. Add the subproject as a remote
1. Init the subproject (using SSH to be able to contribute back)
1. Fetch the subtree
1. Pull latest changes


```
git remote add <subproject_name> ssh://git@github.com/<package_git_url>.git
git subtree add --prefix <path_to_subproject> <subproject_name> master --squash
git fetch <subproject_name> master
git subtree pull --prefix <path_to_subproject> <subproject_name> master --squash
```

### Commit and contribute to subproject

1. Make some changes to any files
1. Commit your changes
1. Push changes to your project and to the subproject

```
touch <path_to_subproject>/
git subtree push --prefix=<path_to_subproject> <subproject_name> master
```

### Remove a subtree

```
git rm -r <path_to_subproject>
git remote remove <subproject_name> 
git filter-branch --index-filter 'git rm --cached --ignore-unmatch -rf <path_to_subproject>' --prune-empty -f HEAD
git reflog expire --expire-unreachable=now --all
git gc --prune=now
```


# Cstom Composer scripts

Your subproject might need automated tasks to be run.

A Drupal theme for instance most likely needs to generate assets such as CSS, minify JS...etc

You need to manually those custom Composer scripts to your main project, as follow: 
```
  "scripts": {
    "...",
    "subproject-script-name": "SubprojectNamespace\\SubprojectClass::taskToRun",
    "post-install-cmd": [
      "...",
      "@subproject-script-name"
    ],
    "post-update-cmd": [
      "...",
      "@subproject-script-name"
    ]
  }
```

The [Drupal Pyramid theme](https://github.com/drupal-pyramid/drupal_pyramid_theme) is a real-world example of a subproject with custom scripts to generate assets. As you can see in [its README file](https://github.com/MatthieuScarset/drupal_pyramid_theme#getting-started), you have to add your own custom scripts to `composer.json`. 

---

Any questions? Please have a look at [the wiki](https://github.com/drupal-pyramid/drupal-pyramid/wiki) or [open a new issue](https://github.com/drupal-pyramid/drupal-pyramid/issues).
