#!/bin/bash
HTMLDIR=/var/www/html
TFTPDIR=/tftpboot

# Clean PXE Boot files from pxelinux.cfg directory
logger -p daemon.info "Cleaning PXE Boot files..."
#cd /tftpboot/pxelinux.cfg

# Removes Legacy PXE Boot files
find $TFTPDIR/pxelinux.cfg -name "01-*" -type f -mmin +60 -delete

#Removes UEFI Grub Boot files
find $TFTPDIR -name "01-*" -type f -mmin +60 -delete

#rm -f 01-*

# Clean Kickstart/PreSeed files from HTML directory
logger -p daemon.info "Cleaning Kickstart/Preseed files..."
#cd /var/www/html/
#rm -f *.cfg

find $HTMLDIR -name "*.cfg" -type f -mmin +60 -delete

logger -p daemon.info "Clean up complete"

