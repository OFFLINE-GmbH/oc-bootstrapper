# Bootstrapper for October CMS

`oc-bootstrapper` is a simple script that enables you to bootstrap an October CMS installation
with custom plugins and custom themes. You simply describe your setup in a config file and run
the install command.
 
 `oc-bootstrapper` enables you to install plugins and themes from your own git repo. 

The following steps will be taken care of:

1. The latest October CMS gets downloaded from github and gets installed
2. All composer dependencies are installed
3. Relevant config entries are moved to a `.env` file for easy customization
4. Sensible configuration defaults for your `prod` environment get preset
5. Your database gets migrated
6. All demo data gets removed
7. Your selected theme gets downloaded and installed
8. All your plugins get downloaded and installed 
9. A .gitignore file gets created 
10. A push to deploy setup gets initialized for you 

## Dependencies

* Zip PHP extension (`sudo apt-get install php7.0-zip`) 
* Composer (via global binary or `composer.phar` in your working directory) 

## Tested on

* Ubuntu 15.10

Should work on OS X. Will probably not work on Windows.


## Installation

```composer global require offline/oc-bootstrapper``` 

You can now run `october` from your command line.

```bash
$ october
October CMS Bootstrapper version 0.1.0
```

## Usage

### Initialize your project

Use the `october init` command to create a new project with a config file:

```sh
october init myproject.com
cd myproject.com
```

### Change your configuration

In your newly created project directory you'll find an `october.yaml` file. Edit its contents
to suite your needs.

```yaml
app:
    url: http://october.dev
    locale: en
    debug: true

cms:
    theme: name (user@remote.git)
    edgeUpdates: false

database:
    connection: mysql
    username: homestead
    password: secret
    database: bootstrapper
    host: 192.168.10.10

deployment: gitlab

plugins:
    - Rainlab.Pages
    - Rainlab.Builder
    - Adrenth.Redirect
    - Indikator.Backend
    - OFFLINE.SiteSearch
    - OFFLINE.ResponsiveImages
    # - Vendor.Private (user@remote.git)

mail:
    name: User Name
    address: email@example.com
    driver: log
```

#### Theme and Plugin syntax

`oc-bootstrapper` enables you to install plugins and themes from your own git repo. Simply
append your repo's address in `()` to tell `oc-bootstrapper` to check it out for you.
If no repo is defined the plugins are loaded from the October Marketplace.


### Install October CMS

When you are done editing your configuration file, simply run `october install` to install October. 

### Change config

To change your installation's configuration, simply edit the `.env` file in your project root. 
When deploying to production, make sure to edit your `.env.production` template file and rename it to `.env`.  

### SSH deployments

Set the `deployment` option to `false` if you don't want to setup deployments.

Currently `oc-bootstrapper` supports a simple setup to deploy a project on push via GitLab CI. To automatically create all needed files, simply set the `deployment` option to `gitlab`.

Support for other CI systems is added on request.

#### GitLab CI with Envoy

If you use the gitlab deployment option the `.gitlab-ci.yml` and `Envoy.blade.php` files are created for you.

Change the variables inside the `Envoy.blade.php` to fit your needs. 

If you push to your GitLab server and CI builds are enabled, the ssh tasks inside `Envoy.blade.php` are executed. Make sure that your GitLab CI Runner user can access your target server via `ssh user@targetserver`. You'll need to copy your ssh public key to the target server and enable password-less logins via ssh.

For more information on how to use ssh keys during a CI build see [http://doc.gitlab.com/ce/ci/ssh_keys/README.html](http://doc.gitlab.com/ce/ci/ssh_keys/README.html)

##### Cronjob to commit changes from prod into git

If a deployed website is edited by a customer directly on the prod server you might want to commit
 those changes back to your git repository. 
 
To do this, simply create a cronjob that executes `git.cron.sh` every X minutes. This script will commit all changes
to your git repo automatically.

### File templates

You can overwrite all default file templates by creating a folder called `october` in your global composer directory.
Usually it is located under `~/.composer/`.

Place the files you want to use as defaults in `~/.composer/october`. All files from the `templates` directory can be overwritten.

## ToDo

- [ ] Update command to update private plugins

## Troubleshooting

### Fix cURL error 60 on Windows using XAMPP

If you are working with XAMPP on Windows you will most likely get the following error during `october install`:

    cURL error 60: SSL certificate problem: unable to get local issuer certificate
    
You can fix this error by executing the following steps.

1. Download the `cacert.pem` file from [VersatilityWerks](https://gist.github.com/VersatilityWerks/5719158/download)
2. Extract the `cacert.pem` to the `php` folder in your xampp directory (eg. `c:\xampp\php`)
3. Edit your `php.ini` (`C:\xampp\php\php.ini`). Add the following line.

   `curl.cainfo = "\xampp\php\cacert.pem"`

`october install` should work now as expected.

