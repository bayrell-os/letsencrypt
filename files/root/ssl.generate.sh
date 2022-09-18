#!/bin/bash

sudo -E -u www php /var/www/html/console.php ssl:generate $@
