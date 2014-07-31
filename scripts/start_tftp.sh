#!/bin/bash
TFTPDIR=/tftpboot

# Start TFTP Server
/usr/sbin/in.tftpd -l -c -v -v -v -u root -m /etc/tftpd.map -s $TFTPDIR

# Refresh NFS exports
exportfs -r
