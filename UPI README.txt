MANIFEST

index.php			--- Main GUI/TFTP/UEFI file generator
admin.php		--- Admin GUI for adding/removing OS entries as well as adding/removing/modifying TFTP/Kickstart/Preseed templates
app.js				--- Javascript file for index.php (makes the menus work and misc form checks)
admin.js			--- Javascript file for admin.php (makes the menus work and misc form checks)
upload.php		--- File upload/ISO download processor file (backend for uploading ISOs to the system)
db_lib.php		--- Backend database interface for the javascript applications to pull data from the database
config.php			--- Holds mySQL credentials in order to access the database

Modification of these files requires PHP, HTML, JavaScript, and mySQL knowledge.

Extra files are in /root for processing the ISO images as well.

centos_pxe_copy.sh		--- Copies the installer kernel and RAMfs into the tftp directory for booting
oel_pxe_copy.sh				--- Copies the installer kernel and RAMfs into the tftp directory for booting
ubuntu_pxe_copy.sh		--- Depreciated as we use a local Ubuntu mirror now
debian_pxe_copy.sh		--- Depreciated as we use a local Debian mirror now
esxi_pxe_copy.sh			--- Copies boot.cfg to writeable directory and modifies boot.cfg to point to the mounted ISO for install files
fedora_pxe_copy.sh		--- Copies the installer kernel and RAMfs into the tftp directory for booting
clean_pxe_files.sh			--- Removes any PXE generated files once they are 60 minuets old (cron job set)
fstab_update.sh				--- Updates FSTab to auto mount specific ISOs as needed for installation (ie FreeBSD)
remove_os.sh					--- Removes ISOs and related files when an OS has been specified to be removed (currently disabled)
start_tftp.sh						--- Backup to use in case TFTPd does not start automagicly

Modification of these files requires BASH script and familiarity with used Linux command line applications (sed, awk, etc)
