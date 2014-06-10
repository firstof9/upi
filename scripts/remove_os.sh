#!/bin/bash

if [ $1 == "" ]; then
        echo "Error: Missing directory location"
        exit 1
fi

if [ $2 == "" ]; then
	echo "Error: Missing ISO directory location"
	exit 1
fi

if [ $1 == "/" ]; then
	echo "Error: Cannot remove /"
	exit 1
fi

# Creating syslog entries
logger -p daemon.info "Request to remove an OS"
logger -p daemon.info "Umounting $2"

# Unmount the ISO folder
umount $2


### Curently disabled
## Log etries only beign made right now

# Remove the OS directory
logger -p daemon.info "Removing directory $1"
#rm -rf $1

# Remove fstab entry
logger -p daemon.info "Removing fstab entry for $1"
ENTRY=`echo $1 | sed s,/,\\\\\\\/,g`
#sed -i $ENTRY'/d' /etc/fstab

# Debuging
#sed -n $TEST'/p' /etc/fstab
