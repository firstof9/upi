#!/bin/bash

if [ $1 == "" ]; then
	echo "Missing parameter"
	exit 1
fi

logger -p daemon.info "Adding fstab entry $1"

# Add ISO to fstab auto mount
echo $1 $2 "	iso9660 mode=444,loop	0	0" >> /etc/fstab
echo "fstab updated"

