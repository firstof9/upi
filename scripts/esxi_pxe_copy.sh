#!/bin/bash

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp /tftpboot/boot/esxi/$1/iso/boot.cfg /tftpboot/boot/esxi/$1/
sed -i.bak s,/,iso/,g /tftpboot/boot/esxi/$1/boot.cfg

exportfs -r

echo "PXE Install files ready for ESXi $1"

