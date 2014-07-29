#!/bin/bash
$HTMLDIR=/var/www/html
$TFTPDIR=/tftpboot

logger -p daemon.info "Fixing everything!"
echo -e "Fixing everything!\n"
echo -en "\nIn progress...\n"
logger -p daemon.info "Fixing $HTMLDIR..."
chmod 777 $HTMLDIR

logger -p daemon.info "Fixing $TFTPDIR/pxelinux.cfg..."
chmod 777 $TFTPDIR/pxelinux.cfg

logger -p daemon.info "Fixing $TFTPDIR..."
chmod 777 $TFTPDIR

logger -p daemon.info "Restarting apache...."
service httpd restart

logger -p daemon.info "Restarting tftpd...."
/root/start_tftp.sh

logger -p daemon.info "Restarting DHCPd..."
service dhcpd restart

logger -p daemon.info "Everything fixed!"
echo -e "\nEverything fixed!\n"
