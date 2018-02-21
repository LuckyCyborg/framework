<?php

/**
 * Console - register the Forge Commands and Schedule
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 *
 */


/**
 * Resolve the Forge commands from application.
 */
Forge::resolveCommands(array(
    //'App\Console\Commands\MagicWand',
));


/**
 * Add the Closure based commands.
 */
Forge::command('queue:monitor-listener', function ()
{
    $runCommand = true;

    if (file_exists($pidFile = storage_path('queue.pid'))) {
        $pid = file_get_contents($pidFile);

        $runCommand = empty(
            exec("ps -p $pid --no-heading | awk '{print $1}'")
        );
    }

    if ($runCommand) {
        $command = PHP_BINARY .' ' .base_path('forge') .' queue:work --daemon --tries=3 >/dev/null & echo $!';

        $number = exec($command);

        file_put_contents($pidFile, $number);
    }

})->describe('Monitor the Queue Listener');


/**
 * Schedule the Queue Listener's Monitor.
 */
Schedule::command('queue:monitor-listener')->everyFiveMinutes();

// Alternate method working also under Windows.
//Schedule::command('queue:work --daemon')->everyFiveMinutes()->withoutOverlapping();

/**
 * Schedule the flushing of expired password reminders.
 */
Schedule::command('auth:clear-reminders')->daily();

/**
 * Schedule the Database Backup.
 */
Schedule::command('db:backup')->dailyAt('4:30');
