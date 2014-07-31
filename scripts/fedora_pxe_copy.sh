#!/bin/bash
TFTPDIR=/tftpboot

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp $TFTPDIR/boot/fedora/$1/iso/images/pxeboot/vmlinuz $TFTPDIR/boot/fedora/$1/
cp $TFTPDIR/boot/fedora/$1/iso/images/pxeboot/initrd.img $TFTPDIR/boot/fedora/$1/
mkdir $TFTPDIR/boot/fedora/$1/images/
cp $TFTPDIR/boot/fedora/$1/iso/images/install.img $TFTPDIR/boot/fedora/$1/images/
cp $TFTPDIR/boot/fedora/$1/iso/images/stage2.img $TFTPDIR/boot/fedora/$1/images/

# Keep ISO mounted for URL install
#umount /tftpboot/boot/fedora/$1/iso
#rmdir /tftpboot/boot/fedora/$1/iso/

# This shouldn't be needed as the path /tftpboot/boot/fedora is already shared
#echo "/tftpboot/boot/fedora/$1 192.168.2.0/255.255.255.0(rw,insecure,no_subtree_check,nohide)" >> /etc/exports
exportfs -r

echo "PXE Install files ready for Fedora $1"

