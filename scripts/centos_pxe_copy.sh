#!/bin/bash

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp /tftpboot/boot/centos/$1/iso/images/pxeboot/vmlinuz /tftpboot/boot/centos/$1/
cp /tftpboot/boot/centos/$1/iso/images/pxeboot/initrd.img /tftpboot/boot/centos/$1/
mkdir /tftpboot/boot/centos/$1/images/
cp /tftpboot/boot/centos/$1/iso/images/install.img /tftpboot/boot/centos/$1/images/
cp /tftpboot/boot/centos/$1/iso/images/stage2.img /tftpboot/boot/centos/$1/images/

# Keep ISO mounted for URL install
#umount /tftpboot/boot/centos/$1/iso
#rmdir /tftpboot/boot/centos/$1/iso/

# This shouldn't be needed as the path /tftpboot/boot/centos is already shared
#echo "/tftpboot/boot/centos/$1 192.168.2.0/255.255.255.0(rw,insecure,no_subtree_check,nohide)" >> /etc/exports
exportfs -r

echo "PXE Install files ready for CentOS $1"

