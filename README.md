# Bootstrapper for October CMS

`oc-bootstrapper` is a simple script that enables you to bootstrap an October CMS installation
with custom plugins and custom themes. You simply describe your setup in a config file and run
the install command.
 
 `oc-bootstrapper` enables you to install plugins and themes from your own git repo. 

The following steps will be taken care of:

1. The latest October CMS gets downloaded from github and gets installed
2. All composer dependencies get installed
3. A `dev` environment gets created for you where all your settings from the config file are set
4. Some sensible configuration defaults for your `prod` environment get set
5. Your database gets migrated
6. All demo data gets removed
7. Your selected theme gets downloaded and installed
8. All your plugins get downloaded and installed 
9. A .gitignore file gets created 

## Dependencies

* Zip PHP extension (`sudo apt-get install php7.0-zip`) 
* Composer (via global binary or `composer.phar` in your working directory) 

## Tested on

* Ubuntu 15.10
* Windows 7 (using `Git Bash`) 


## Installation

```composer global require offline/oc-bootstrapper``` 

You can now run `october` from your command line.

```bash
$ october
October CMS Bootstrapper version 0.0.7
```

### Fix cURL error 60 on Windows using XAMPP

If you are working with XAMPP on Windows you will most likely get the following error during `october install`:

    cURL error 60: SSL certificate problem: unable to get local issuer certificate
    
You can fix this error by executing the following steps.

1. Download the `cacert.pem` file from [VersatilityWerks](https://gist.github.com/VersatilityWerks/5719158/download)
2. Extract the `cacert.pem` to the `php` folder in your xampp directory (eg. `c:\xampp\php`)
3. Edit your `php.ini` (`C:\xampp\php\php.ini`). Add the following line.

   `curl.cainfo = "\xampp\php\cacert.pem"`

`october install` should work now as expected.


## Usage

### Initialize your project

Use the `october init` command to create a new project with a config file:

```sh
cd /var/www
october init myproject.com
```

### Change your configuration

In your newly created project directory you'll find an `october.yaml` file. Edit its contents
to suite your needs.

```yaml
app:
    url: http://october.dev
    locale: en

database:
    connection: mysql
    username: homestead
    password: secret
    database: bootstrapper
    host: 192.168.10.10

# What theme to install
theme: name (user@remote.git)

# What plugins to install
plugins:
    - Rainlab.Pages
    - Rainlab.Builder
    - OFFLINE.SiteSearch
    - OFFLINE.ResponsiveImages
    # - Vendor.Private (user@remote.git)

# Default mail settings (mail.php)
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

## Features

- [X] Prod / Dev Environment Config in yaml
- [X] Mail Config mail.from.address mail.from.name mail.driver
- [X] Copy cms and app config into dev environment
- [X] php artisan october:fresh
- [X] Install plugins
- [X] Clear compiled / cache
- [X] Create .gitignore
- [ ] Implement an update command to update custom plugins