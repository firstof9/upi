@echo off
:: This script prepares a Windows 7 image for PXE
:: This assumes you have the WAIK installed 
:: Author: Louis A
:: Date: 3/31/2013
:: Modified: 6/26/2013

:: Added Driver injection
:: by: Chris N
:: 11/11/13

:: Any modification of this file is strictly on you and is not recommended. 
:: Modify only if you know what you're doing. 

:: Variables below.
:: The next few reference file and directory paths that are essential
:: Change BASEDIR if you would like a different directory to deploy the PE environment
:: Change ARCH to x86 if you need a 32 Bit PE instead
set WAIKPATH=C:\Program Files (x86)\Windows Kits\8.1\Assessment and Deployment Kit
set ARCH=amd64
set BASEDIR=C:\winpe
set PEPATH=%BASEDIR%\winpe_%ARCH%
set TFTPPATH=%BASEDIR%\tftp\Boot
set BCDSTORE=%TFTPPATH%\BCD
set SOURCE=C:
set OSCDImgRoot=%PROGRAMFILES(X86)%\Windows Kits\8.1\Assessment and Deployment Kit\Deployment Tools\%ARCH%\Oscdimg
set WinPERoot=%PROGRAMFILES(X86)%\Windows Kits\8.1\Assessment and Deployment Kit\Windows Preinstallation Environment

:: These were created because of errors that came up (due to missing variable references)
:: Do not remove these, otherwise you'll see errors that may confuse you, even though they're harmless.
set WAIKMISSING=0
set ARCHMISSING=0
set NOTSANE=0
set ERROR=0

:: Checking if we're elevated
net session >nul 2>&1
if %errorLevel%==0 ( echo Admin Success ) else ( set ERROR=2 && goto :exit )

:: Making sure our environment is sane.
:: We need to make sure our working directory is empty. If it's not, we'll empty it and start over.
if not exist "%WAIKPATH%" ( set WAIKMISSING=1 && goto :end )
if not exist "%WAIKPATH%\Windows Preinstallation Environment\%ARCH%" ( set ARCHMISSING=1 && goto :end )
if not exist "%BASEDIR%" ( md C:\winpe )
if exist "%PEPATH%" ( echo Temporary working directory is not empty! && rd %PEPATH% /S )
if exist "%PEPATH%" ( echo Temporary working directory is still not empty! Are you serious? Trying to delete... again. && cd "%WAIKPATH%\Tools\%ARCH%" && dism /Unmount-Wim /MountDir:%PEPATH%\mount && rd %PEPATH% /S /Q )
if exist "%PEPATH%" ( set NOTSANE=1 && goto :end )
if exist "%TFTPPATH%" ( echo TFTP boot directory is not empty! && rd %TFTPPATH% /S )
if exist "%TFTPPATH%" ( set NOTSANE=1 && goto :end )
if exist "%BCDSTORE%" ( echo BCD store already exists! && del /P %BCDSTORE% )
if exist "%BCDSTORE%" ( set NOTSANE=1 && goto :end )

:: Environment appears to be sane... beginning work.
:: This is the grunt work. Do not change anything below unless you know what you're doing.
cd "%WAIKPATH%\Windows Preinstallation Environment\"
echo "Copying the PE Files"
call copype %ARCH% %PEPATH%
echo "Mounting the PE image with dism"
:: This was added because I forgot the concept of mounting in Linux :) The folder must exist.
if not exist "%PEPATH%\mount" ( md %PEPATH%\mount )
dism /Mount-Wim /WimFile:%PEPATH%\media\sources\boot.wim /Index:1 /MountDir:%PEPATH%\mount /ScratchDir:c:\temp
md %TFTPPATH% > NUL
copy %PEPATH%\mount\Windows\Boot\PXE\*.* %TFTPPATH% > NUL
copy "%WAIKPATH%\Windows Preinstallation Environment\%ARCH%\boot\boot.sdi" %TFTPPATH% > NUL

:: Add scripting support
echo Adding scripting support...
dism /Add-Package /Image:"%PEPATH%\mount" /PackagePath:"%WAIKPATH%\Windows Preinstallation Environment\%ARCH%\WinPE_OCs\WinPE-setup.cab" /ScratchDir:c:\temp
dism /Add-Package /Image:"%PEPATH%\mount" /PackagePath:"%WAIKPATH%\Windows Preinstallation Environment\%ARCH%\WinPE_OCs\WinPE-setup-server.cab" /ScratchDir:c:\temp
dism /Add-Package /Image:"%PEPATH%\mount" /PackagePath:"%WAIKPATH%\Windows Preinstallation Environment\%ARCH%\WinPE_OCs\WinPE-Scripting.cab" /ScratchDir:c:\temp
dism /Add-Package /Image:"%PEPATH%\mount" /PackagePath:"%WAIKPATH%\Windows Preinstallation Environment\%ARCH%\WinPE_OCs\WinPE-wds-tools.cab" /ScratchDir:c:\temp
dism /Add-Package /Image:"%PEPATH%\mount" /PackagePath:"%WAIKPATH%\Windows Preinstallation Environment\%ARCH%\WinPE_OCs\WinPE-wmi.cab" /ScratchDir:c:\temp

:: Inject Drivers
echo Importing drivers from %SOURCE%\drivers\CURRENT
dism /image:%PEPATH%\mount /Add-Driver /driver:%SOURCE%\drivers\CURRENT /Recurse /ScratchDir:c:\temp

bcdedit /createstore %BCDSTORE%
bcdedit /store %BCDSTORE% /create {ramdiskoptions} /d "Ramdisk Options"
bcdedit /store %BCDSTORE% /set {ramdiskoptions} ramdisksdidevice Boot
bcdedit /store %BCDSTORE% /set {ramdiskoptions} ramdisksdipath  \boot\windows\boot.sdi
for /f "Tokens=3" %%x in ('bcdedit /store %BCDSTORE% /create /d "Windows PXE Installation" /application osloader') do set GUID=%%x
bcdedit /store %BCDSTORE% /set %GUID% systemroot \Windows
bcdedit /store %BCDSTORE% /set %GUID% detecthal Yes
bcdedit /store %BCDSTORE% /set %GUID% winpe Yes
bcdedit /store %BCDSTORE% /set %GUID% osdevice ramdisk=[boot]\boot\windows\boot.wim,{ramdiskoptions}
bcdedit /store %BCDSTORE% /set %GUID% device ramdisk=[boot]\boot\windows\boot.wim,{ramdiskoptions}
bcdedit /store %BCDSTORE% /create {bootmgr} /d "Windows Boot Manager"
bcdedit /store %BCDSTORE% /set {bootmgr} timeout 30
bcdedit /store %BCDSTORE% /set {bootmgr} displayorder %GUID%
bcdedit /store %BCDSTORE%

:: This was added because at BCD loading, it will freeze and then complain of missing fonts
md %TFTPPATH%\fonts
copy %PEPATH%\ISO\boot\fonts\*.* %TFTPPATH%\fonts
echo Please add files to %PEPATH%\mount then
pause
:: If the command below fails, make sure to use imagex to unmount C:\winpe\winpe_amd64\mount
dism /Unmount-Wim /MountDir:mount /commit
copy %PEPATH%\media\sources\boot.wim %TFTPPATH% > NUL
goto :exit

:end
:: Environment was not sane.
if %WAIKMISSING%==1 ( echo Your WAIK directory was not found. Execution aborted. && set ERROR=1 && goto :exit )
if %ARCHMISSING%==1 ( echo Architecture is either missing or is not recognized.  && set ERROR=1 && goto :exit )
if %NOTSANE%==1 ( echo Your environment was not clean. Execution aborted. && set ERROR=1 && goto :exit )
 
:exit
if %ERROR%==1 ( echo There was an error and execution was aborted. )
if %ERROR%==2 ( echo You were not elevated. Please launch an elevated command prompt. )
cd %BASEDIR%