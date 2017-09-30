# Drupal Mastaba

A solution to work on multiple projects from within a single repository.

![An ancient Egyptian Mastaba](https://storiesfromthemuseumfloor.files.wordpress.com/2016/04/the-mastaba-tombs-a.jpg?w=334&h=230)


## Getting started

Create a new project with Composer:
```
composer create-project matthieuscarset/drupal-mastaba:8.x-dev <your_project_name> --stability dev --no-interaction
```

Alternatively, you can clone this repo and remove the Git history:
```
git clone git@github.com:MatthieuScarset/drupal-mastaba.git <your_project_name>
rm -rf <your_project_name>/.git
git init
git add .
git commit -m "Initial commit (Drupal Mastaba project)"
composer install
```

You can now start to add sub-projects to your project.


## Add a new sub-project

Following is an example of adding a new custom modules as a subproject.

```
# Download dependency with Composer
composer require <namespace>/<subproject_name>:~1.0
# OR clone it in your custom modules folder
git clone git@github.com:<namespace>/<subproject_name>.git custom/modules/<subproject_name>

# Add the subproject as a remote to your main project (using SSH to be able to contribute back to it)
git remote add <subproject_name> ssh://git@github.com/<package_git_url>.git

# Add the subtree
git subtree add --prefix <path_to_subproject>/<subproject_name> <subproject_name> master --squash

# Update the subtree
git fetch <subproject_name> master
git subtree pull --prefix <path_to_subproject>/<subproject_name> <subproject_name> master --squash

# Contribute back to subproject
git subtree push --prefix=<path_to_subproject>/<subproject_name> <subproject_name> master

# Remove a subtree
git rm -r <path_to_subproject>
git remote remove <subproject_name> 
git filter-branch --index-filter 'git rm --cached --ignore-unmatch -rf <path_to_subproject>' --prune-empty -f HEAD
git reflog expire --expire-unreachable=now --all
git gc --prune=now
```

## Add sub-project custom scripts

Your subproject might have to automatic tasks to run. 

Drupal themes for instance surely has to generate assets such as CSS, minify JS...etc

The [Drupal Mastaba theme](https://github.com/MatthieuScarset/drupal_mastaba_theme) is a real-world example of a subproject with custom scripts to generate assets. As you can see in [its README file](https://github.com/MatthieuScarset/drupal_mastaba_theme#getting-started), you have to add your own custom scripts to `composer.json`. 

Add your subproject Composer scripts: 
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

Any questions? Please have a look at [the wiki](https://github.com/MatthieuScarset/drupal-mastaba/wiki) or [open a new issue](https://github.com/MatthieuScarset/drupal-mastaba/issues).
