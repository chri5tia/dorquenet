#!/bin/sh
#
# Cloud Hook: update-db
#
# Run drush updatedb in the target environment. This script works as
# any Cloud hook.

site="$1"
target_env="$2"

# drush @$site.$target_env updb -y
# drush @$site.$target_env cim -y
echo "Testing, testing. Hello world!"
