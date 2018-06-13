# Drupal Pyramid

A starter kit for your Drupal projects with instructions to work on multiple subprojects.

![BrowserStack Status](https://www.browserstack.com/automate/badge.svg?badge_key=BvpY1y5yRRsxyUxUVDLJ)

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

## How to install

We recommend you install [Lando](https://docs.devwithlando.io/installation/installing.html).

* Init your environment
* install dependencies
* Create local settings file
* Install Drupal
* Set your website name...etc
* Export your new settings
* Secure your installation
* Commit your work
* [Enjoy](http://gph.is/*uVl0T)!

```
lando init --recipe drupal8 --webroot web --name <project_name>
cd <project_name>
cp example.drush.yml .drush.yml
lando start
lando composer install
lando composer site-install
lando composer site-reset-user
lando composer site-reset-config
git init
git add .
git commit -m "Initial install and update website info"
```

You now have working Drupal website at [https://<project_name>.lndo.site](https://<project_name>.lndo.site)


## How to add subproject

### TL;DR

![Git subtree workflow](http://drupal-pyramid.org/img/git_subtree_cmds.jpg)

You should have a look at the [Drupal Pyramid theme](https://github.com/drupal-pyramid/drupal_pyramid_theme). This is a real-world example of a subproject with a **custom Composer type** and **custom Composer scripts** to generate assets. Read [its README file](https://github.com/drupal-pyramid/drupal_pyramid_theme#getting-started) to understand how to add this as a Git subtree to your current project.

You should also read [that great tutorial](https://www.atlassian.com/blog/git/alternatives-to-git-submodule-git-subtree) from Atlassian.


### Step-by-step

1) Add a composer.json file to your subproject:
```
{
  "name": "drupal-pyramid/drupal_pyramid_theme",
  "description": "A theme for Drupal Pyramid project theme.",
  "type": "drupal-theme-custom",
  ...
```

2) Back in this project, add the subproject Git URL:
```
"repositories": [
  {
    "type": "composer",
    "url": "https://packages.drupal.org/8"
  },
  {
    "type": "git",
    "url": "ssh://git@github.com:<namespace>/<subproject_name>.git"
  }    
],
```

3) Add your custom composer type to this project `composer.json` file.  

This is possible thanks to the `oomphinc/composer-installers-extender` dependency.

```
 "extra": {
    "installer-types": [
      "drupal-config",
      "drupal-theme-custom"
    ],
    "installer-paths": {
      ...
      "web/themes/custom/{$name}": [
        "type:drupal-theme-custom"
      ],
      "config/{$name}": [
        "type:drupal-config"
      ],
      "drush/contrib/{$name}": [
        "type:drupal-drush"
      ]
    },
    ...
```

4) Add the subproject as a remote
5) Init the subproject (using SSH to be able to contribute back)
6) Fetch the subtree
7) Pull latest changes
```
git remote add <subproject_name> git@github.com/<package_git_url>.git
git subtree add --prefix <path_to_subproject> <subproject_name> master --squash
git fetch <subproject_name> master
git subtree pull --prefix <path_to_subproject> <subproject_name> master --squash
```

8) Make some changes and commit your changes
```
touch <path_to_subproject>/
git add .
git commit -m "Made some changes to something interesting."
```

9) Push changes to your project and to the subproject
```
git push
git subtree push --prefix=<path_to_subproject> <subproject_name> master
```


## How to remove a subtree project

```
git rm -r <path_to_subproject>
git remote remove <subproject_name> 
git filter-branch --index-filter 'git rm --cached --ignore-unmatch -rf <path_to_subproject>' --prune-empty -f HEAD
git reflog expire --expire-unreachable=now --all
git gc --prune=now
```


## How to use subproject custom scripts

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


## How to apply patches

We use `cweagans/composer-patches` to add patches to our dependencies.

You can add patches under the `extra` object in `composer.json` as follow:

```
"extra": {
  ...
  "patches": {
    "drupal/address": {
      "Drupal Addess fix default syncing": "https://www.drupal.org/files/issues/address_syncing.patch"     
    }
  },
  ...
```

Now you can apply it with Composer:
```
lando composer update drupal/address
```


---


Any questions? Please have a look at [the wiki](https://github.com/drupal-pyramid/drupal-pyramid/wiki) or [open a new issue](https://github.com/drupal-pyramid/drupal-pyramid/issues).
