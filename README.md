upi
===

Universal PXE Installer

TODO

Upload pxelinux files

Instructions for installing

PREREQUESTS

Apache
PHP
MySQL
TFTPd

Bootstrap (www.getbootstrap.com)

CentOS:
yum -y install apache php mysql-server tftp-server


You can download bootstrap here: http://www.getbootstrap.com

OR

wget https://github.com/twbs/bootstrap/releases/download/v3.1.1/bootstrap-3.1.1-dist.zip

INSTALL

scripts -- These files go in /root

html -- These files go in your webroot ie: /var/www/html

mysql -- holds the database dump to be imported to your MySQL database

templates -- Various automation template examples

tftp -- These files go in your tftproot directory ie: /tftpboot or /var/tftproot

windows scripts -- These are files that are to be added to your Windows PE boot image for automating Windows installs
