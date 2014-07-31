#!/bin/bash
TFTPDIR=/tftpboot

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp $TFTPDIR/boot/ubuntu/$1/iso/install/netboot/ubuntu-installer/amd64/linux $TFTPDIR/boot/ubuntu/$1/
cp $TFTPDIR/boot/ubuntu/$1/iso/install/netboot/ubuntu-installer/amd64/initrd.gz $TFTPDIR/boot/ubuntu/$1/

echo "PXE boot files ready for Ubuntu $1"

