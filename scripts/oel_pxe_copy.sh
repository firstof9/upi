#!/bin/bash
TFTPDIR=/tftpboot

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp $TFTPDIR/boot/oel/$1/iso/images/pxeboot/vmlinuz $TFTPDIR/boot/oel/$1/
cp $TFTPDIR/boot/oel/$1/iso/images/pxeboot/initrd.img $TFTPDIR/boot/oel/$1/
mkdir $TFTPDIR/boot/oel/$1/images/
cp $TFTPDIR/boot/oel/$1/iso/images/install.img $TFTPDIR/boot/oel/$1/images/
cp $TFTPDIR/boot/oel/$1/iso/images/stage2.img $TFTPDIR/boot/oel/$1/images/

# Keep ISO mounted for URL install
#umount /tftpboot/boot/oel/$1/iso
#rmdir /tftpboot/boot/oel/$1/iso/

# This shouldn't be needed as the path /tftpboot/boot/oel is already shared
#echo "/tftpboot/boot/oel/$1 192.168.2.0/255.255.255.0(rw,insecure,no_subtree_check,nohide)" >> /etc/exports
exportfs -r

echo "PXE Install files ready for OEL $1"

