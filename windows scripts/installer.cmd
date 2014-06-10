@ECHO OFF

echo.
echo Restoring the Setup environment
move %SYSTEMDRIVE%\setup.bak %SYSTEMDRIVE%\setup.exe
move %SYSTEMDRIVE%\sources\setup.bak %SYSTEMDRIVE%\sources\setup.exe

echo.
echo Setting unattended install file

set UNATTEND=\\192.168.2.1\install\%1

SET REGEXE=%SYSTEMDRIVE%\windows\system32\reg.exe

echo.
echo Removeing PXE information from registry to prevent WDS install
%REGEXE% delete HKLM\SYSTEM\CurrentcontrolSet\Control\PXE /f

echo.
echo Starting setup with unattend file %UNATTEND%
%SYSTEMDRIVE%\setup.exe /unattend:%UNATTEND%