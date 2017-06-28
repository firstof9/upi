#!/bin/bash


# Abort if not ran as root/sudo

if [[ $(id -u) -ne 0 ]]; then echo "Please run as root" ; exit 1 ; fi


# OS Detection

if [ -f /etc/lsb-release ]; then
	. /etc/lsb-release
	OS=$DISTRIB_ID
	VER=$DISTRIB_RELEASE
elif [ -f /etc/debian_version ]; then
	OS=Debian
	VER=$(cat /etc/debian_version)
elif [ -f /etc/centos_version ]; then
	OS=CentOS
	VER=$(cat /etc/centos_version)

# TODO add rest of release types ie: RedHat and Ubuntu

else
	OS=$(uname -s)
	VER=$(uname -r)
fi

# Add errors for 'unsupported' versions here?


if [ "$OS" = "CentOS" ]; then
	echo "Downloading needed packages...";
	PACKAGES="apache php mariadb-server tftp-server syslinux-tftpboot ipmitool"
	YUM=$(yum -y install $PACKAGES)
	
	if [ "$YUM" -ne 0]; then
		echo "An error occured installing required packages:";
		rpm --query --queryformat "" $PACKAGES
		exit 1;
	fi
	
	echo "Copying files to /var/www/html ... "
	CP=$(cp -R html/ /var/www)
	
	if [ "$CP" -ne 0 ]; then
		echo "There was a problem copying the files to /var/www/html, please check if the directory exists.";
		exit 1;
	fi
	
	
# TODO add rest of release types installers	
	
fi

# Copy directories to correct file structure
# Create needed database
# Import database file structure
