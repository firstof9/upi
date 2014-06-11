rem Install IIS and ASP.NET - Dependency for for Plesk
rem TODO: Test this on 2008 as well - if it doesn't work, add some logic to only run this on 2012
echo %date% %time% >> c:\Tools\pleskinstall.log
Start /w pkgmgr /iu:IIS-WebServerRole;IIS-WebServer;IIS-CommonHttpFeatures;IIS-StaticContent;IIS-DefaultDocument;IIS-DirectoryBrowsing;IIS-HttpErrors;IIS-ApplicationDevelopment;IIS-ASPNET;IIS-NetFxExtensibility;IIS-ISAPIExtensions;IIS-ISAPIFilter;IIS-HealthAndDiagnostics;IIS-HttpLogging;IIS-LoggingLibraries;IIS-RequestMonitor;IIS-Security;IIS-RequestFiltering;IIS-HttpCompressionStatic;IIS-WebServerManagementTools;IIS-ManagementConsole;WAS-WindowsActivationService;WAS-ProcessModel;WAS-NetFxEnvironment;WAS-ConfigurationAPI


echo %date% %time% >> c:\Tools\pleskinstall.log
echo "Starting Plesk install" >> c:\Tools\pleskinstall.log
c:\Tools\ai.exe --select-release-id PANEL_11_0_9_WIN --install-component base --install-component awstats --install-component mailenable --install-component dns --install-component spamassassin --install-component mysql-client --install-component phpmyadmin --install-component plesk-migration-manager --install-component php53 --install-component atmail --install-component webmail --no-daemon
echo %date% %time% >> c:\pleskinstall.log
echo "Finished Plesk install" >> c:\Tools\pleskinstall.log
