upi
===

Universal PXE Installer

This system will automagicly generate TFTP boot files for facilitating the installation of your desiered OS over the network, completely automated (providing you have a proper templete setup).

DISCLAIMER
===
<strong><mark>This software is beta.</strong></mark>

TODO
===
Create installer script - <strong>In progress</strong>

Instructions for installing

Update batch file for generating Windows PE image (auto inject startnet.cmd, installer.cmd, installer.vbs rename setup.exe)

Code cleanup

PREREQUISITES
===
Apache

NFS (optional for use with NFSISO installs on CentOS 6+)

PHP

MySQL

TFTPd

IPMITools (Used to automagicly reboot and set server to PXE Boot off the network)

Bootstrap (www.getbootstrap.com) (This should auto link in via CDN, only download if you want a local copy)

Windows Assessment and Deployment Kit (ADK) (optional for generating Windows PE image)
