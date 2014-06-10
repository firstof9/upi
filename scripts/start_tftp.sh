#!/bin/bash

# Start TFTP Server
/usr/sbin/in.tftpd -l -c -v -v -v -u root -m /etc/tftpd.map -s /tftpboot

# Refresh NFS exports
exportfs -r
