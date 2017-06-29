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
	
	if [ -f /tftpboot ]; then
		echo "Setting up TFTP Server root directory...";
		cd tftp
		cp -fR * /tftpboot
		cd ..
	else
		echo "TFTPBoot location not found.";
		exit 1;
	fi
	
	echo "Copying scripts to /root ...";
	CP=$(cp -R scripts/ /root)
	
	if [ "$CP" -ne 0 ]; then
		echo "There was an error copying files to /root";
		exit 1;
	fi

	echo "Setting service to start on boot...";
	HTTPD=$(systemctl is-enable httpd)
	TFTPD=$(systemctl is-enable tftp-server)
	SQL=$(systemctl is-enable mariadb-server)
	
	if [ "$HTTPD" -ne 1 ]; then
		systemctl enable httpd
	fi
	
	if [ "$TFTPD" -ne 1 ]; then
		systemctl enable tftp-server
	fi
	
	if [ "$SQL" -ne 1 ]; then
		systemctl enable mariadb-server
	fi
	
fi

# TODO add rest of release types installers	

# Copy directories to correct file structure
# Create needed database
# Import database file structure

echo "Install completed!";
exit 0;