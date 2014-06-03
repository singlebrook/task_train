# Task Train

A sequential script runner with a teensy REST API.

Task Train is a task manager that receives, queues, and sequentially runs jobs which can run one or more scripts.

## Requirements

- PHP 5.4 or greater*
- [Composer](https://getcomposer.org)

*If the PHP included with your distro is too old, you'll need to compile a custom version just for running the task manager (don't worry: it's not that bad!). You can keep your system PHP for running websites.

Here's how to compile the latest stable version of PHP (as of this writing) on Debian/Ubuntu. Note that we don't need any bells and whistles - just a php executable with no enabled extensions.

    you@server$ cd ~
    you@server$ sudo aptitude install build-essential
    you@server$ mkdir src
    you@server$ cd src/
    you@server$ curl -O http://us1.php.net/distributions/php-5.5.13.tar.bz2
    you@server$ tar xjvf php-5.5.13.tar.bz2
    you@server$ cd php-5.5.13/
    you@server$ ./configure --prefix=/opt/php --with-config-file-path=/opt --disable-all
    you@server$ make
    you@server$ sudo make install
    you@server$ /opt/php/bin/php -v  # Should return PHP 5.5.13 (cli)

## Installing

The easiest way to install is via git. Just:

    $ git clone http://github.com/singlebrook/task_train.git
    $ cd task_train
    $ composer install

## Running the task train daemon

The simplest way to start it is with the following command, run as your user from the task_train directory:

    you@server$ php server.php

If you installed a newer version of PHP for Task Train, your command might look like this:

    you@server$ /opt/php/bin/php server.php

To run it in the background, so that you can close your terminal:

    you@server$ nohup php server.php &

or this, for a custom php:

    you@server$ nohup /opt/php/bin/php server.php &

> **NOTE:** There are more robust ways to run the task manager as a service. It hasn't been tested, but something like Upstart or [daemontools](http://cr.yp.to/daemontools/install.html) should work.

Test it by running the following:

    you@server$ curl http://127.0.0.1:5800/

You should see this response:

    Task Manager is running!

If the server is running in the background and you need to stop it, you can find its pid and kill it like so:

    you@server$ ps -ax | grep server.php
    63565 ttys000    0:00.12 php server.php
    you@server$ kill 63565

By default, the Task Train server listens on 127.0.0.1:5800.

## Submitting tasks

Tasks are submitted via HTTP calls to a local web server running on port 5800 by default. Here are some examples:

    $ curl -X POST "http://127.0.0.1:5800/task?name=example"
    $ curl -X POST "http://127.0.0.1:5800/task?name=example&myparam=value"
    $ curl -X POST "http://127.0.0.1:5800/task?name=gitdeploy&branch=newfeature&action=create&repo=/home/git/myrepo.git"

A task record will be returned, like so:

    $ curl -X POST "http://127.0.0.1:5800/task?name=example&myparam=testvalue"
    ID:      538d230405898
    NAME:    example
    PARAMS:  {"myparam":"testvalue"}
    STATUS:  pending
    CREATED: Mon, 02 Jun 2014 21:21:08 -0400
    PROCESS: /usr/bin/php process.php --name='example' --myparam="'testvalue'"

Tasks will be run in the background, one at a time, in sequence of submission.

## Creating task scripts

Create a new directory under `scripts.d` and name it after your task name. For example, if you want to create a task that deploys code from a git repository, create a directory named `scripts/gitdeploy` or similar.

Any executable scripts placed in that directory will be run by tasks in glob order. They will be passed a `--name` parameter with the task name as well as any extra URL parameters as script arguments in the form of `--argument=value`.

Scripts will be run as the same user that runs the Task Train server. They will be run as a separate process in the background, and their status can be monitored via the (minimal) web interface.

### Example

If the following task is requested:

    curl -sX POST "http://127.0.0.1:5800/task?name=calsdeploy&branch=fitvids&action=create&repo=/home/cals/cals.git"

And there is a script at `scripts.d/calsdeploy/deploy.php`, that script will be run with these arguments:

    scripts.d/calsdeploy/deploy.php --name=calsdeploy --branch='fitvids' --action='create' --repo='/home/cals/cals.git'

If there were also a script at `calsdeploy/notify.sh`, it would be run, too, like this:

    scripts.d/calsdeploy/notify.sh --name=calsdeploy --branch='fitvids' --action='create' --repo='/home/cals/cals.git'

The scripts do not need to be written in PHP.

### Script Output

Any content echoed or printed in a script can be reviewed at http://127.0.0.1:5800/tasks/TASK_ID while the task is running or when it is completed.

### Exit Codes

Scripts must return an exit code of 0 on success. If any other exit code is returned, the task fails and any unexecuted scripts are not run.

## Checking task status

Visit http://127.0.0.1:5800/tasks to see a list of tasks. Currently, the task list is cleared when the server is restarted. You can click on any taks name to see the status of any running or complete task.

## Proxying
