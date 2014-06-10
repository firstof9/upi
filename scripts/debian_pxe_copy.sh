#!/bin/bash

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

DISTPATH="/tftpboot/boot/debian/$1"

# Download the latest installer
cd $DISTPATH
#wget -nv -N http://ftp.nl.debian.org/debian/dists/stable/main/installer-amd64/current/images/netboot/debian-installer/amd64/linux
#wget -nv -N http://ftp.nl.debian.org/debian/dists/stable/main/installer-amd64/current/images/netboot/debian-installer/amd64/initrd.gz

ln -P /var/www/html/mirrors/debian/dists/stable/main/installer-amd64/current/images/netboot/debian-installer/amd64/linux $DISTPATH/linux
ln -P /var/www/html/mirrors/debian/dists/stable/main/installer-amd64/current/images/netboot/debian-installer/amd64/initrd.gz $DISTPATH/initrd.gz

echo "PXE boot files ready for Debian $1"

