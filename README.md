# Bootstrapper for October CMS

`oc-bootstrapper` is a command line tool that enables you to reconstruct an October CMS installation
from a single configuration file.

It can be used to quickly bootstrap a local development environment for a project or
to build and update a production installation during a deployment.


## Features

* Installs and updates private and public plugins (via Git or Marketplace)
* Makes sure only necessary files are in your git repo by intelligently managing your `.gitignore` file
* Built in support for GitLab CI deployments
* Built in support for shared configuration file templates
* Sets sensible configuration defaults using `.env` files for production and development environments

## Dependencies

* Zip PHP extension (`sudo apt-get install php-zip`) 
* Composer (via global binary or `composer.phar` in your working directory) 

## Tested on

* Ubuntu 15.10
* Ubuntu 16.04
* Ubuntu 18.04
* OSX 10.11 (El Capitan)

Works on Windows via `Ubuntu Bash` or `Git Bash`.

## Example project

While using `oc-bootstrapper` it is a good idea to keep `october.yaml`, project's theme and project's plugins (those that are not shared among other projects) in project's repo.

Take a look at the [OFFLINE-GmbH/octobertricks.com](https://github.com/OFFLINE-GmbH/octobertricks.com) repo to see an example setup of `oc-bootstrapper`.

## Installation

```bash
composer global require offline/oc-bootstrapper
``` 

You can now run `october` from your command line.

```bash
$ october -v
October CMS Bootstrapper version 0.5.0
```

### Docker image

An official Docker image that bundles `oc-bootstrapper`, `composer` and `Envoy` is available on [hub.docker.com](https://hub.docker.com/r/offlinegmbh/oc-bootstrapper/) as `offlinegmbh/oc-bootstrapper`.

```bash
docker run offlinegmbh/oc-bootstrapper october -v
docker run offlinegmbh/oc-bootstrapper envoy -v
docker run offlinegmbh/oc-bootstrapper composer -v
```

It is intended to be used with CI pipelines but can also make getting started with an October project 
even easier as you don't need to install PHP and Composer locally.

You can execute any command in the context of the current working directory by using this docker command:

```bash
# alias this to "october" for easier access
docker run -it --rm -v "$(pwd)":/app offlinegmbh/oc-bootstrapper october
```

Be aware, that this will work great for commands like `october init` as it does not depend on any
external services. To run `october install` some more plumbing is required so the container can connect
to your database.

## Usage

### Initialize your project

Use the `october init` command to create a new empty project with a config file:

```sh
october init myproject.com
cd myproject.com
```

### Change your configuration

In your newly created project directory you'll find an `october.yaml` file. Edit its contents
to suite your needs.

```yaml
app:
    name: my-new-project       # Give the project a unique name
    url: http://october.dev
    locale: en
    debug: true

cms:
    theme: name (user@remote.git)
    edgeUpdates: false
    disableCoreUpdates: false
    enableSafeMode: false
    # project: XXXX            # Marketplace project ID

database:
    connection: mysql
    username: root
    password: 
    database: bootstrapper
    host: localhost

git:
    deployment: gitlab
    keepRepo: false       # Keep .git in plugins

# deployment:            # Automatically configure the Envoy file for GitLab deployments      
#     user: hostinguser  
#     server: servername                    

plugins:
    - Rainlab.Pages
    - Rainlab.Builder
    - Indikator.Backend
    - OFFLINE.SiteSearch
    - OFFLINE.ResponsiveImages
    - OFFLINE.GDPR (https://github.com/OFFLINE-GmbH/oc-gdpr-plugin.git)
    - ^OFFLINE.Mall (https://github.com/OFFLINE-GmbH/oc-mall-plugin.git#develop)
    # - Vendor.Private (user@remote.git)
    # - Vendor.PrivateCustomBranch (user@remote.git#branch)

mail:
    host: smtp.mailgun.org
    name: User Name
    address: email@example.com
    driver: log

```

#### Theme and Plugin syntax

`oc-bootstrapper` enables you to install plugins and themes from your own git repo. Simply
append your repo's address in `()` to tell `oc-bootstrapper` to check it out for you.
If no repo is defined the plugins are loaded from the October Marketplace.

##### Examples

```yaml
# Install a plugin from the official October Marketplace
- OFFLINE.Mall 

# Install a plugin from a git repository. The plugin will be cloned
# into your local repository and become part of it. You can change the
# plugin and modify it to your needs. It won't be checked out again (no updates).
- OFFLINE.Mall (https://github.com/OFFLINE-GmbH/oc-mall-plugin.git)

# The ^ marks this plugin as updateable. It will be removed and checked out again
# during each call to `october install`. Local changes will be overwritten.
# This plugin will stay up to date with the changes of your original plugin repo.
- ^OFFLINE.Mall (https://github.com/OFFLINE-GmbH/oc-mall-plugin.git)

# Install a specific branch of a plugin. Keep it up-to-date.
- ^OFFLINE.Mall (https://github.com/OFFLINE-GmbH/oc-mall-plugin.git#develop)
```


### Install October CMS

When you are done editing your configuration file, simply run `october install` to install October. 
`oc-bootstrapper` will take care of setting everything up for you. You can run this command locally
after checking out a project repository or during deployment.

This command is *idempotent*, it will only install what is missing on subsequent calls. 

```
october install 
```

Use the `--help` flag to see all available options.

```
october install --help 
```

#### Install additional plugins

If at any point in time you need to install additional plugins, simply add them to your `october.yaml` and re-run 
`october install`. Missing plugins will be installed.

  

#### Use a custom php binary

Via the `--php` flag you can specify a custom php binary to be used for the installation commands:

```
october install --php=/usr/local/bin/php72
```
### Update October CMS

If you want to update the installation you can run

```
october update
```
### Push changes to remote git repo

To push local changes to the current git remote run 

```
october push
```

This command can be run as cron job to keep your git repo up-to-date with changes on production.
 
### SSH deployments

Set the `deployment` option to `false` if you don't want to setup deployments.

#### Setup

You can use `oc-bootstrapper` with any kind of deployment software. You need to setup the following steps:

1. Connect to the target server (via SSH)
1. Install composer and oc-bootstrapper
1. Run `october install`

You can run this "script" for each push to your repository. The `october install` command will
only install what is missing from the target server.


#### Example setup for GitLab CI

To initialize a project with GitLab CI support set the `deployment` option in your config file to `gitlab`.

This will setup a [`.gitlab-ci.yml`](templates/gitlab-ci.yml) and a [`Envoy.blade.php`](templates/Envoy.blade.php). 

1. Create a SSH key pair to log in to your deployment target server
1. Create a `SSH_PRIVATE_KEY` variable in your GitLab CI settings that contains the created private key
1. Edit the `Envoy.blade.php` script to fit your needs
1. Push to your repository. GitLab will run the example `.gitlab-ci.yml` configuration


### Cronjob to commit changes from prod into git

If a deployed website is edited by a customer directly on the prod server you might want to commit
 those changes back to your git repository. 
 
To do this, simply create a cronjob that executes `october push` every X minutes. This command will commit all changes
to your git repo automatically with message `[ci skip] Added changes from $hostname`.

### File templates

You can overwrite all default file templates by creating a folder called `october` in your global composer directory.
Usually it is located under `~/.config/composer`. 

Place the files you want to use as defaults in `~/.config/composer/october`. All files from the `templates` directory can be overwritten.

On Windows you can store your files in `%USERPROFILE%/AppData/Roaming/Composer/october/`

### Variable replacements

It is possible to use placeholders in your configuration files which will be replaced by 
values from your october.yaml configuration:

```php
// Example Envoy.blade.php
$url = '%app.url%'; // Will be replaced by the app.url value from your october.yaml file
```

There is a special placeholder `%app.hostname%` available that will be replaced by the host part
of your `app.url`:

```ini
%app.url%      = http://october.dev
%app.hostname% = october.dev
``` 

#### File templates from a git repository

If your templates folder is a git repository `oc-bootstrapper` will pull the latest changes for the repo every time 
you run `october init`.

This is a great feature if you want to share the template files with your team via a central 
git repository. Just make sure you are able to fetch the latest changes via `git pull` and you're all set!

```bash
cd ~/.config/composer/october
git clone your-central-templates-repo.git .
git branch --set-upstream-to=origin/master master
git pull # Make sure this works without any user interaction
```


### Development environments

`oc-bootstrapper` can set up a development environment for you. Currently, only [Lando](https://lando.dev/) is supported
out of the box.

To enable the Lando integration, run `october init` and select `lando` as a dev environment. A `.lando.yml` file will be placed in your project. 
 
You can now simply run `lando start` to get everything up and running inside a Docker environment created by Lando.