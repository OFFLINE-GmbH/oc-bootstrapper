@setup
    $project   = 'project-title';
    $user      = 'username';
    $server    = 'servername';

    $directory = 'public_html/';
@endsetup

@servers(['web' => $user . '@' . $server])

@task('deploy', ['on' => 'web'])
    cd {{ $directory }}
    git pull
    [ ! -f "composer.phar" ] && wget https://getcomposer.org/composer.phar
    php composer.phar install --no-interaction --no-dev --prefer-dist
    php artisan -v october:up
    git status -s
@endtask

@after
    $message = 'Deployed project ' . $project;
    // @slack('webhook', 'deployments', $message)
@endafter