# Bootstrapper for October CMS

`oc-bootstrapper` is a simple script that enables you to bootstrap an October CMS installation
with custom plugins and custom themes. You simply describe your setup in a config file and run
the install command.
 
 `oc-bootstrapper` enables you to install plugins and themes from your own git repo. 

The following steps will be taken care of:

1. The latest October CMS gets downloaded from github and gets installed
2. All composer dependencies are installed
3. Relevant config entries are moved to a `.env` file for easy customization
4. Sensible configuration defaults for your `prod` environment get pre-set
5. Your database gets migrated
6. All demo data gets removed
7. Your selected theme gets downloaded and installed
8. All your plugins get downloaded and installed 
9. A .gitignore file gets created 
10. A push to deploy setup gets initialized for you 

## Dependencies

* Zip PHP extension (`sudo apt-get install php-zip`) 
* Composer (via global binary or `composer.phar` in your working directory) 

## Tested on

* Ubuntu 15.10
* Ubuntu 16.04
* OSX 10.11 (El Capitan)

Works on Windows via Ubuntu Bash or Git Bash.


## Installation

```composer global require offline/oc-bootstrapper``` 

You can now run `october` from your command line.

```bash
$ october
October CMS Bootstrapper version 0.2.0
```

### Docker image

An official Docker image that bundles `oc-bootstrapper`, `composer` and `Envoy` is available on [hub.docker.com](https://hub.docker.com/r/offlinegmbh/oc-bootstrapper/) as `offlinegmbh/oc-bootstrapper`.

```bash
docker exec offlinegmbh/oc-bootstrapper -v
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
    enableSafeMode: false

database:
    connection: mysql
    username: homestead
    password: secret
    database: bootstrapper
    host: 192.168.10.10

git:
    deployment: false
    
    bareRepo: true          # Exclude everything except themes and custom plugins in git  
    excludePlugins: false   # Even exclude plugins from your repo. Private plugins will be
                            # checked out again during each "install" run. Be careful!
                            # Manual changes to these plugins will be overwritten.        

plugins:
    - Rainlab.Pages
    - Rainlab.Builder
    - Indikator.Backend
    - OFFLINE.SiteSearch
    - OFFLINE.ResponsiveImages
    - OFFLINE.Indirect (https://github.com/OFFLINE-GmbH/oc-indirect-plugin.git)
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


### Install October CMS

When you are done editing your configuration file, simply run `october install` to install October. 

```
october install 
```

Use the `--help` flag to see all available options.

```
october install --help 
```
 
#### Use a custom php binary

Via the `--php` flag you can specify a custom php binary to be used for the installation commands:

```
october install --php=/usr/local/bin/php72
```

#### Install additional plugins

If at any point in time you need to install additional plugins, simply add them to your `october.yaml` and rerun `october install`. Missing plugins will be installed.

### Change config

To change your installation's configuration, simply edit the `.env` file in your project root. 
When deploying to production, make sure to edit your `.env.production` template file and rename it to `.env`.  

### Bare repos

If you don't want to have the complete October source code in your repository set the `bareRepo`
 option to `true`.
 
 This will set up a `.gitignore` file that excludes everything except your `theme` directory and all the **manually installed** plugins in your `plugins` directory.
  
  > If you want to deploy a bare repo please read the section `SSH deployments with bare repos` below.
  
#### `excludePlugins`

By default every private plugin will be cloned only once and is then added to your `.gitignore` file. In the end your bare repo includes your theme and all your custom and private plugins. If you wish to only include your theme and no plugin data at all you can set `excludePlugins` to true.

If you run `october install` in an existing project (let's say during deployment) all private plugin directories will get remove from your local disk and are checked out via git again so you'll get the latest version. 

If you don't want a plugin to be checked out on every `october install` run you can add the following line to your `.gitignore` file. If such a line is found the plugin will not be touched after the first checkout.

```
# vendor.plugin
# offline.sitesearch 
```

#### Get up and running after `git clone`

After cloning a bare repo for the first time, simply run `october install`. October CMS and all missing plugins will get installed locally on your machine. 
  
### SSH deployments

Set the `deployment` option to `false` if you don't want to setup deployments.

Currently `oc-bootstrapper` supports a simple setup to deploy a project on push via GitLab CI. To automatically create all needed files, simply set the `deployment` option to `gitlab`.

Support for other CI systems is added on request.

 #### SSH deployments with bare repos
  
  If you use SSH deployments with a bare repo, make sure to  run `./vendor/bin/october install` in your deployment script to install the October source code and all of your plugins . If the October source code is already available it won't be downloaded again.

If you use the provided GitLab deployment via Envoy make sure to simply uncomment [this line](https://github.com/OFFLINE-GmbH/oc-bootstrapper/blob/fd45b66580f4b1af24880a3b331635a7654cf4ed/templates/Envoy.blade.php#L17).
  
  It is important that you list every installed plugin in your `october.yaml` file. Otherwise the plugins won't be available after deployment.
  
#### GitLab CI with Envoy

If you use the gitlab deployment option the `.gitlab-ci.yml` and `Envoy.blade.php` files are created for you.

Change the variables inside the `Envoy.blade.php` to fit your needs. You have to create a Pipeline Variable in GitLab called `SSH_PRIVATE_KEY` that contains your private key. Add your public key to the target server.

If you push to your GitLab server and CI builds are enabled, your private key is added to the `ssh-agent` inside the Docker container and the tasks from `Envoy.blade.php` will be executed on your target server. 

##### Cronjob to commit changes from prod into git

If a deployed website is edited by a customer directly on the prod server you might want to commit
 those changes back to your git repository. 
 
To do this, simply create a cronjob that executes `git.cron.sh` every X minutes. This script will commit all changes
to your git repo automatically.

### File templates

You can overwrite all default file templates by creating a folder called `october` in your global composer directory.
Usually it is located under `~/.config/composer`.

Place the files you want to use as defaults in `~/.config/composer/october`. All files from the `templates` directory can be overwritten.

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

