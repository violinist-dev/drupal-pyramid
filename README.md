# Drupal Pyramid

A solution to work on multiple projects from within a single repository.

![An ancient Egyptian Pyramid](http://drupal-pyramid.org/img/pyramids.jpg)


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

Init your environment and install dependencies:
```
lando init --recipe drupal8 --webroot web --name <project_name>
lando start
lando composer install
```

Install Drupal:
```
sudo cp web/sites/example.settings.local.php web/sites/default/settings.local.php
lando drush si config_installer -r web -y 
```

Secure your installation:
```
lando drush user-create yourname --password="yourpassword" --mail="your@email.com" -r web -y
lando drush user-add-role "administrator" --name=yourname -r web -y
lando drush user-password admin --password=admin -r web -y
lando drush user-block --name="admin" -r web -y
```

You now have working Drupal website at [https://<project_name>.lndo.site](https://<project_name>.lndo.site)

Done! And it works... :) [Happy now](https://media.giphy.com/media/13r9tgg7ZisiT6/giphy.gif)?

## Add a new sub-project

Following is an example of adding a new custom modules as a subproject.

### Download subproject 

Preferably, you would host your subproject in a [Packagist](https://packagist.org/) server so you can download if as a dependency with Composer.
```
composer require <namespace>/<subproject_name>:~1.0
```

Alternatively, you can clone it from a Git repository.
```
git clone git@github.com:<namespace>/<subproject_name>.git custom/modules/<subproject_name>
```

### Add the subproject as a remote

Add the subproject as a new Git remote in your main project. 

Use the SSH address to be able to contribute back to it or the HTTP(S) if you need read-only access only.

```
git remote add <subproject_name> ssh://git@github.com/<package_git_url>.git
```

### Initialize the Git subtree

```
git subtree add --prefix <path_to_subproject>/<subproject_name> <subproject_name> master --squash
```

### Update the subtree

```
git fetch <subproject_name> master
git subtree pull --prefix <path_to_subproject>/<subproject_name> <subproject_name> master --squash
```

### Contribute back to subproject

```
git subtree push --prefix=<path_to_subproject>/<subproject_name> <subproject_name> master
```

### Remove a subtree

```
git rm -r <path_to_subproject>
git remote remove <subproject_name> 
git filter-branch --index-filter 'git rm --cached --ignore-unmatch -rf <path_to_subproject>' --prune-empty -f HEAD
git reflog expire --expire-unreachable=now --all
git gc --prune=now
```
## Add your custom scripts to Composer

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
