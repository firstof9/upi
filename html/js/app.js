function generatePassword() {
	var length = 16,
		charset = "abcdefghijknopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ0123456789",
		retVal = "";
	for (var i = 0, n = charset.length; i < length; ++i) {
		retVal += charset.charAt(Math.floor(Math.random() * n));
	}
	return retVal;
}

var App = {
    /**
     * The onReady method, init everything
     */
    ready: function () {
        this.watchRequiredVariablesForm();
		this.buttons();
		$('[data-toggle=tooltip]').tooltip();
		$(".carousel").carousel({interval: false});
    },
	
	buttons: function() {
		$("#generate").click(function(ev) {
			ev.preventDefault();
			var password = generatePassword();
			$("#password").val(password);
		});
		
		$("#submit").click(function(ev) {
			ev.preventDefault();
			$("#requiredVariables").submit();
		});
		
		$("#submit1").click(function(ev) {
			ev.preventDefault();
			$("#requiredVariables").submit();
		});		
		
		$("#vnc").change(function () {
			var vString = $("#vnc").val();
			if (vString == 1) {
				$("#mAlert div.modal-dialog div.modal-content div.modal-body span.message").html("<p>Please note if the VNC connection fails the installer will not continue.</p><br><p>You will need a VNC viewer listening on port <b>5500</b> (make sure no firewall settings are blocking this port)</p>" );
				$("#mAlert").modal('show');
			}
		});
		$('#back1').click(function(ev) {
			ev.preventDefault();
			$('#mainCarousel').carousel('prev');
		});				
		$('#back').click(function(ev) {
			ev.preventDefault();
			$('#mainCarousel').carousel('prev');
		});		
		$('#next').click(function(ev) {
			ev.preventDefault();
			$('#mainCarousel').carousel('next');
		});
		$('#disk1').click(function(ev) {
			ev.preventDefault();
			$("#mAlert div.modal-dialog div.modal-content div.modal-body span.message").html("<p>Be advised this will <b>WIPE</b> all data from <b>/dev/sdb</b></p><br><p>If you are doing a <b><em>slaved reinstall</em></b> please disconnect the slaved drive and reconnect after the OS has been installed.</p>" );
			$("#mAlert").modal('show');
			$('#mainCarousel').carousel('next');
		});		
		$("#resetForm2").click(function(ev) {
			ev.preventDefault();
			/* $('html, body').animate({scrollTop:0}, 'fast'); */
			$("#requiredVariables")[0].reset();
		});				
		$("#resetForm1").click(function(ev) {
			ev.preventDefault();
			/* $('html, body').animate({scrollTop:0}, 'fast'); */
			$("#requiredVariables")[0].reset();
		});		
		$("#resetForm").click(function(ev) {
			ev.preventDefault();
			/* $('html, body').animate({scrollTop:0}, 'fast'); */
			$("#requiredVariables")[0].reset();
		});
		$("#is_gpt").change(function() {
			var oString = $('#os option:selected').text();
			var windowsDisk = new RegExp("Windows").test(oString);
			var use_gpt = $("#is_gpt option:selected").val();
			
			if (windowsDisk == true  && use_gpt == false) 
			{
				/* Default partition 1 */
				$('#disk0mount1').val("System");
				$("#disk0size1").val("grow");
				$("#disk0fs1").val("NTFS");
				/* Default partition 2 */
				$("#disk0mount2").val("");
				$("#disk0size2").val("");
				$("#disk0fs2").val("");
				/* Default parition 3 */
				$("#disk0mount3").val("");
				$("#disk0size3").val("");
				$("#disk0fs3").val("");
			}
			else if (windowsDisk == true && use_gpt == true) 
			{
				/* Default partition 1 */
				$('#disk0mount1').val("EFI");
				$("#disk0size1").val("200");
				$("#disk0fs1").val("EFI");
				/* Default partition 2 */
				$("#disk0mount2").val("MSR");
				$("#disk0size2").val("128");
				$("#disk0fs2").val("MSR");
				/* Default parition 3 */
				$("#disk0mount3").val("System");
				$("#disk0size3").val("grow");
				$("#disk0fs3").val("NTFS");
			}
			/* Linux Partitions */
			else
			{
				/* Default partition 1 */
				$("#disk0mount1").val("/boot");
				$("#disk0size1").val("500");
				$("#disk0fs1").val("ext3");					
				/* Default partition 2 */
				$("#disk0mount2").val("swap");
				$("#disk0size2").val("4096");
				$("#disk0fs2").val("swap");					
				/* Default partition 3 */						
				$("#disk0mount3").val("/");
				$("#disk0size3").val("grow");
				$("#disk0fs3").val("ext4");					
			}	
		});
		$("#os").change(function() {
			var qString = $('#os').val();
			var oString = $('#os option:selected').text();
			
			var use_gpt = $("#is_gpt option:selected").val();
			
			if (qString == "0") 
			{ 
				$("#submit").prop('disabled',true);  
				$("#submit1").prop('disabled',true); 
			}
			else 
			{ 
				$("#submit").prop('disabled',false); 
				$("#submit1").prop('disabled',false); 
			}
			
			var windowsDisk = new RegExp("Windows").test(oString);
			var esxiDisk = new RegExp("ESXi").test(oString);
			if (windowsDisk == true  && use_gpt == false) 
			{
				/* Default partition 1 */
				$('#disk0mount1').val("System");
				$("#disk0size1").val("grow");
				$("#disk0fs1").val("NTFS");
				/* Default partition 2 */
				$("#disk0mount2").val("");
				$("#disk0size2").val("");
				$("#disk0fs2").val("");
				/* Default parition 3 */
				$("#disk0mount3").val("");
				$("#disk0size3").val("");
				$("#disk0fs3").val("");
			}
			else if (windowsDisk == true && use_gpt == true) 
			{
				/* Default partition 1 */
				$('#disk0mount1').val("EFI");
				$("#disk0size1").val("200");
				$("#disk0fs1").val("EFI");
				/* Default partition 2 */
				$("#disk0mount2").val("MSR");
				$("#disk0size2").val("128");
				$("#disk0fs2").val("MSR");
				/* Default parition 3 */
				$("#disk0mount3").val("System");
				$("#disk0size3").val("grow");
				$("#disk0fs3").val("NTFS");
			}
			else if (esxiDisk == true)
			{
				/* Default partition 1 */
				$('#disk0mount1').val("datastore1");
				$("#disk0size1").val("grow");
				$("#disk0fs1").val("vmwfs");
				/* Default partition 2 */
				$("#disk0mount2").val("");
				$("#disk0size2").val("");
				$("#disk0fs2").val("");
				/* Default parition 3 */
				$("#disk0mount3").val("");
				$("#disk0size3").val("");
				$("#disk0fs3").val("");				
			}
			/* Linux Partitions */
			else
			{
				/* Default partition 1 */
				$("#disk0mount1").val("/boot");
				$("#disk0size1").val("500");
				$("#disk0fs1").val("ext3");					
				/* Default partition 2 */
				$("#disk0mount2").val("swap");
				$("#disk0size2").val("4096");
				$("#disk0fs2").val("swap");					
				/* Default partition 3 */						
				$("#disk0mount3").val("/");
				$("#disk0size3").val("grow");
				$("#disk0fs3").val("ext4");					
			}			
			
			$.ajax({
				type: 'POST',
				data:  ({mode: "comment", id : qString}),
				url: 'db_lib.php',
				success: function(data) {
						$("#osComment").html($("#osComments").html());
						$("#osComment div.col-md-10").addClass('text-info');
						$("#osComment div.col-md-10 span.message").html("<p>" + data + "</p>");
				}
			});	
			$.ajax({
				type: 'POST',
				data:  ({mode: "data", id : qString}),
				url: 'db_lib.php',
				success: function(data) {
					var allowedDisk = new RegExp("Ubuntu").test(oString);
					var array = data.split("|");
					if (array[0] == 1 && allowedDisk == false) { $("#disk1").prop('disabled',false); }
					else { $("#disk1").prop('disabled',true); }
					
				}
			});				
			$.ajax({
				type: 'POST',
				data:  ({mode: "info", id : qString}),
				url: 'db_lib.php',
				success: function(data) {
					var array = data.split("|");
					if (array[2] == 1) { $("#can_plesk").prop('disabled',false); }
					else { $("#can_plesk").prop('disabled',true); $("#can_plesk").prop('checked',false); $("#none").prop('checked',true); }
					if (array[3] == 1) { $("#can_cpanel").prop('disabled',false); }
					else { $("#can_cpanel").prop('disabled',true); $("#can_cpanel").prop('checked',false); $("#none").prop('checked',true); }
										
				}
			});				
			var allowedVNC = new RegExp("(CentOS|Oracle Enterprise Linux)").test(oString);
			if (allowedVNC == true) {
				$("#vnc").prop('disabled', false);
			}
			else { 
				$("#vnc").val("0");
				$("#vnc").prop('disabled', true); 
			}
			var disallowPrivNet = new RegExp("(Xen Server|VMware ESXi|ESXi)").test(oString);
			if (disallowPrivNet == true) { $("#privnet").prop('disabled',true); }
			else { $("#privnet").prop('disabled',false); }
		});
		$("#public_ip").change(function() {
			if ($("#public_ip") == "") { return; }
			var data = $("#public_ip").val();
			var myarr = data.split(".");
			$("#public_gateway").val(myarr[0] + "." + myarr[1] + "." + myarr[2] + ".1");
		});	
	},
	
    /**
     * Monitor the Required Variables form for submittal
     */
    watchRequiredVariablesForm: function () {
        $('#requiredVariables').validate({
			rules: {
				"password": {required: true, minlength: 8},
				"public_ip": {ipv4: true, required: true},
				"public_mac": {mac_address: true, required: true, minlength: 17},
				"public_gateway": {ipv4: true, required: true},
				"public_netmask": {ipv4: true, required: true},
				"os": { min: 1, required: true },
			},		
			messages: {
				"password": { required: "required", minlength: 'Minimum of 8 charaters' },
				"public_ip": { required: "required", ipv4: "Enter a valid IP Address"},
				"public_mac": { required: "required", mac_address: 'The MAC address entered is not valid', minlength: 'Invalid MAC address' },
				"public_gateway": { required: "required", ipv4: "Enter a valid IP Address"},
				"public_netmask": { required: "required", ipv4: "Enter a valid IP Address"},
				"os": { required: "required", min: "Please select an OS"},
			},
			highlight: function (label) {
				$(label).closest('.form-group').addClass('has-error');
			},
			success: function (label) {
				$(label).closest('.form-group').addClass('has-success');
			},
			invalidHandler: function(event, validator) {
				var errors = validator.numberOfInvalids();
				if (errors) {
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-danger');
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ban-circle'></span> Required fields missing data.");
				}
			},
			submitHandler: function(form) {
				if($('input[name=control_panel]:checked').val() == "cpanel") {
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-info-sign'></span> Note: cPanel will install after server has been racked. If the install fails you can execute it via SSH with the command: nohup /root/cpanel-installer.sh &");
				}
				if($('input[name=control_panel]:checked').val() == "plesk") {
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-info-sign'></span> Note: Plesk will install after server has been racked. If the install fails you can execute it via SSH with the command: nohup /root/plesk-installer.sh &");
				}
				$.ajax({
					url: $(form).action,
					type: $(form).method,
					data: $(form).serialize(),
					success: function (response) {					
						if (response == "success")
						{
							$("#alertHolder").append($("#alertTemplate").html());
							$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ok-circle'></span> PXE boot files generated. Please PXE boot the server.");
							$('html, body').animate({scrollTop:0}, 'fast');
							var audio = new Audio('audio/working.mp3');
							audio.play();		
						}
						else
						{
							$("#mAlert div.modal-dialog div.modal-content div.modal-header h3.mAlertLabel").html("<span class='glyphicon glyphicon-warning-sign'></span> Error");
							$("#mAlert div.modal-dialog div.modal-content div.modal-body span.message").html("<p><strong>Error: </strong><br>" + response);
							$("#mAlert").modal('show');
							var audio = new Audio('audio/error.mp3');
							audio.play();		
							//$("#alertHolder").append($("#alertTemplate").html());
							//$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-danger');
							//$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ban-circle'></span> An error occured: "+response);					
						}
					},
					error: function (response,status,error) {
						//$("#alertHolder").append($("#alertTemplate").html());
						//$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-danger');
						//$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ban-circle'></span> An error occurred while submitting the data.<br>"+response);
						//$('html, body').animate({scrollTop:0}, 'fast');					
						//val err = eval ("(" + response.responseText + ")");
						$("#alertHolder").append($("#alertTemplate").html());
						$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-danger');
						$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ban-circle'></span> An error occured: "+response);
					}
				});
			}
		});
	}
};

/**
 * Start our application
 */
$(document).ready(App.ready.bind(App));