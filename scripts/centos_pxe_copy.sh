#!/bin/bash
TFTPDIR=/tftpboot

if [ $1 == "" ]; then
	echo "Missing version number"
	exit 1
fi

cp $TFTPDIR/boot/centos/$1/iso/images/pxeboot/vmlinuz $TFTPDIR/boot/centos/$1/
cp $TFTPDIR/boot/centos/$1/iso/images/pxeboot/initrd.img $TFTPDIR/boot/centos/$1/
mkdir $TFTPDIR/boot/centos/$1/images/
cp $TFTPDIR/boot/centos/$1/iso/images/install.img $TFTPDIR/boot/centos/$1/images/
cp $TFTPDIR/boot/centos/$1/iso/images/stage2.img $TFTPDIR/boot/centos/$1/images/

# Keep ISO mounted for URL install
#umount /tftpboot/boot/centos/$1/iso
#rmdir /tftpboot/boot/centos/$1/iso/

# This shouldn't be needed as the path /tftpboot/boot/centos is already shared
echo "/tftpboot/boot/centos/$1 (rw,insecure,no_subtree_check,nohide)" >> /etc/exports
exportfs -r

echo "PXE Install files ready for CentOS $1"

