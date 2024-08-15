#!/bin/bash

## This is meant to easily run console commands from a crontab like:
# SHELL=/opt/akeneo/bin/cron-wrapper.sh
# 0 3 * * * {user} pim:some-command --args=are --allowed
# It runs the command and writes the output (with timestamps) to a file in
# var/logs/

# bash strict-mode
set -euo pipefail

# The entire command line is sent as a single arg
console_command="$2" # first arg is always '-c'
# Strip off any space-separated args to the console command
console_command_name="${console_command%% *}"
CONSOLE="docker compose run -u www-data --rm php php bin/console"
LOG_FILE="var/logs/$console_command_name.log"

cd "$( dirname -- "${BASH_SOURCE[0]}" )"/..
(
   echo "Running console command: $console_command"
   $CONSOLE --no-ansi $console_command 2>&1
) | ts >> "$LOG_FILE"
