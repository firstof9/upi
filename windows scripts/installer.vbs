Set WshShell = WScript.CreateObject("WScript.Shell")
'Set runCmd1 = "wpeinit"
'WshShell.run runCmd1

strComputer = "."
Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
Set colItems = objWMIService.ExecQuery ("Select * from Win32_NetworkAdapterConfiguration WHERE IPEnabled=TRUE")

For Each objItem in colItems
GetMACAddress = objItem.MACAddress
Next

CfgFilename = Replace(GetMACAddress,":","-") + ".cfg"
		
runCmd2 = "%SYSTEMDRIVE%\sources\installer.cmd " + CfgFilename
WshShell.Run runCmd2

'Dim MyVar
'MyVar = MsgBox(CfgFilename,1,"Config filename")
