# Pyramid Drupal

A Drupal 8 multi-repositories project example. 

A solution to work on multiple project from within a single repo.


## Usage

First you need to clone this repo and remove `.git` directory:
```
git clone <this_project>.git <your_project_name>
rm -rf <your_project_name>/.git
git init
git add .
git commit -m "Initial commit (copy from Pyramid)"
composer install
```

You can now start to add sub-projects to your project.


## Multi-repos strategy (in 5 steps)

Multi-repos strategy is a flexible way of build up your project for collaboration and reusability.

It is based on [Git subtree](https://www.atlassian.com/blog/git/alternatives-to-git-submodule-git-subtree).

1. Get your sub-project.
2. Add the sub-project as remote.
3. Add the subtree.
4. Update the subtree.
5. Contribute back to sub-project.


Comands are listed below. 

You need to replace `<namespace>`, `<package_name>` and `<path_to_subproject_subproject>`.

```
# Download it with Composer
composer require <namespace>/<package_name>:~1.0
# OR clone it
cd module/custom 
git clone git@bitbucket.org:<namespace>/<package_name>.git

# Add the remote as read-only
git remote add -f <package_name> https://bitbucket.org/<namespace>/<package_name>.git
# OR using SSH to be able to contribute back to it
git remote add <package_name> ssh://git@bitbucket.org/<namespace>/<package_name>.git

# Add the subtree to the main git
git subtree add --prefix web/<path_to_subproject>/<package_name> <package_name> master --squash

# Update the subtree
git fetch <package_name> master
git subtree pull --prefix web/<path_to_subproject>/<package_name> <package_name> master --squash

# Contribute back to subtree
git subtree push --prefix=web/<path_to_subproject>/<package_name> <package_name> master

# Remove a subtree
git rm -r <path_to_subproject>
git remote remove <package_name> 
git filter-branch --index-filter 'git rm --cached --ignore-unmatch -rf <path_to_subproject>' --prune-empty -f HEAD
git reflog expire --expire-unreachable=now --all
git gc --prune=now
```


Real-world example can be found in [Pyramid Drupal Theme]()'s README file.

## Composer-based template

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Creates default writable versions of `settings.php` and `services.yml`.
* Creates `web/sites/default/files`-directory.
* Latest version of drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.

---

## FAQ

### Why is Git subtree so slow?

That's a known issue with Git subtrees. 

[SplitSH](https://github.com/splitsh/lite) is a solution developped by Symfony teams in `C++` to make things faster. 
However, it needs a learning curve and requires some customization to be implemented in your day-to-day workflow.  

### Should I commit the contrib modules I download?

Please, **don't**! 

See [this argumentation](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md) if you disagree.

### Should I commit the scaffolding files?

Please, **don't**! 

The [drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold) plugin download the scaffold files to `web/` directory of your project after `composer install` or `composer update` (see `composer.json` > `scripts` section).

**Default included files are listed in our `.gitignore` file.**

You can configure the plugin with providing some settings in the `extra` section of your root `composer.json`.
```json
{
  "extra": {
    "drupal-scaffold": {
      "source": "http://cgit.drupalcode.org/drupal/plain/{path}?h={version}",
      "excludes": [
        "google123.html",
        "robots.txt"
      ],
      "includes": [
        "sites/default/example.settings.my.php"
      ],
      "initial": {
        "sites/default/default.services.yml": "sites/default/services.yml",
        "sites/default/default.settings.php": "sites/default/settings.php"
      },
      "omit-defaults": false
    }
  }
}
```
The `source` option may be used to specify the URL to download the scaffold files from; 

The `excludes` option let you provide additional paths that should not be copied or overwritten;

The `omit-defaults` option set to `true` means that only files explicitly listed in the `excludes` and `includes` options will be considered. If `omit-defaults` is `false` (the default), then any items listed in `excludes`
or `includes` will be in addition to the usual defaults.

The `initial` hash lists files that should be copied only if they do not exist in the destination. The key specifies the path to the source file, and the value indicates the path to the destination file.


### How can I apply patches to downloaded modules?

You should use [composer-patches](https://github.com/cweagans/composer-patches) plugin.

This is how to add a patch to a drupal module (see `composer.json` > `extra` section).
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "https://www.drupal.org/files/issues/d8-2855248-1.patch"
        }
    }
}
```

### How to update Drupal Core

This project aims to keep all of your Drupal Core files up-to-date by using [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold).

Your scaffold files are updated every time drupal/core is updated.

Follow the steps below to update your core files.

1. Run `composer update drupal/core --with-dependencies` to update Drupal Core and its dependencies.
1. Run `git diff` to determine if any of the scaffolding files have changed. 
   Review the files for any changes and restore any customizations to 
  `.htaccess` or `robots.txt`.
1. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
1. In the event that there are non-trivial conflicts in step 2, you may wish 
   to perform these steps on a branch, and use `git merge` to combine the 
   updated core files with your customized files. This facilitates the use 
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple; 
   keeping all of your modifications at the beginning or end of the file is a 
   good strategy to keep merges easy.

---

## Credits

Credits to [those people](https://github.com/orgs/drupal-composer/people) for having created and to maintain the [Drupal Composer template](https://github.com/drupal-composer/drupal-project) which this project is based on.

Credits to [@durdn](https://twitter.com/durdn) for the 5 steps instructions about Git Subtree.

Credits to [Fabien Potencier](https://twitter.com/fabpot) for its great talk about [Many repos vs Monolith repo strategy](https://www.dotconferences.com/2016/05/fabien-potencier-monolithic-repositories-vs-many-repositories) here. The long version is on [YouTube](https://www.youtube.com/watch?v=4w3-f6Xhvu8). 
