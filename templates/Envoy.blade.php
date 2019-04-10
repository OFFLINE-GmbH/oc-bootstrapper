@setup
    // Deployment configuration ----------
    $project      = 'example.com';
    $user         = 'hostinguser';
    $server       = 'servername';
    $directory    = 'public_html/example.com/';
    $slackWebhook = 'https://hooks.slack.com/services/your/slack/webhook/url';
    // -----------------------------------

    $author = isset($author) ? $author : "someone";
    $branch = isset($branch) ? $branch : "unknown branch";
    $commit = isset($commit) ? $commit : "no message";
@endsetup

@servers(['web' => $user . '@' . $server, 'localhost' => '127.0.0.1'])

@story('deploy')
    update
@endstory

@task('update', ['on' => 'web'])
    cd {{ $directory }}
    git pull

    [ ! -f "composer.phar" ] && wget https://getcomposer.org/composer.phar
    php composer.phar install --no-interaction --no-dev --prefer-dist --ignore-platform-reqs
    php ./vendor/bin/october install
    php artisan -v october:up

    ## START UPDATE CHECK
        LOCK_FILE=".last-update-check"
        NOW=$(date +%s)
        LAST_CHECK=$( [ -f $LOCK_FILE ] && cat $LOCK_FILE || echo 0 )
        SECONDS_SINCE=$(expr $NOW - $LAST_CHECK)

        if [ "$SECONDS_SINCE" -gt "86400" ]; then
            HOSTNAME=$( hostname )
            GIT=$( which git )

            $PHP composer.phar self-update
            php ./vendor/bin/october update

            if [[ -n $(git status -s) ]]; then
                $GIT add --all .
                $GIT commit -m "[ci skip] oc-bootstrapper updated October CMS ({{ $project }})"
                $GIT push origin master
            fi

            echo $NOW > $LOCK_FILE
        else
            echo "Skipping update check (last check was $SECONDS_SINCE seconds ago)"
        fi
    ## END UPDATE CHECK

    git status -s
@endtask

@finished
    $message = sprintf(
        "`%s` deployed `%s` via `%s`:\n\n> %s",
        ucfirst($author),
        $project,
        $branch,
        $commit
    );
    @slack($slackWebhook, 'deployments', $message)
@endfinished