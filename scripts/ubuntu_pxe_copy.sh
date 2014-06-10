#!/bin/bash

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp /tftpboot/boot/ubuntu/$1/iso/install/netboot/ubuntu-installer/amd64/linux /tftpboot/boot/ubuntu/$1/
cp /tftpboot/boot/ubuntu/$1/iso/install/netboot/ubuntu-installer/amd64/initrd.gz /tftpboot/boot/ubuntu/$1/

echo "PXE boot files ready for Ubuntu $1"

