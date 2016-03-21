# Bootstrapper for October CMS

## Dependencies

* Zip PHP extension (`sudo apt-get install php7.0-zip`) 

## Commands

### init

1. Create project folder and october.yaml

### install

1. Download latest october from https://github.com/octobercms/october/archive/master.zip
2. Unzip
3. Migrate database

## Todo

- [X] Prod / Dev Environment Config in yaml
- [X] Mail Config mail.from.address mail.from.name mail.driver
- [X] Copy cms and app config into dev environment
- [ ] php artisan october:fresh
- [ ] Install plugins (php artisan plugin:install Rainlab.Pages)
- [ ] Clear compiled / cache
- [ ] Create .gitignore

```
.DS_Store
*.log
*node_modules*
.idea
vendor
```
