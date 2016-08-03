#!/bin/bash

nohup /usr/bin/php5 /home/vagrant/scripts/updater/updater.php >/dev/null 2>&1 &

if [ -f /home/vagrant/scripts/updater/UPDATE_AVAILABLE ]; then
    VERSION=`cat /home/vagrant/scripts/updater/UPDATE_AVAILABLE`
    echo ""
    echo "New release $VERSION available for joomlatools/console."

    read -r -p "${1:-Do you want to update now? [y/N]} " response
    case $response in
        [yY][eE][sS]|[yY])
            sudo composer self-update
            composer global require joomlatools/console:$VERSION
            rm -f /home/vagrant/scripts/updater/UPDATE_AVAILABLE
            ;;
        *)
            exit
            ;;
    esac
fi

