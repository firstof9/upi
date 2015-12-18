function refreshPie() {
	var ret = null;
	$.ajax({
			async: false,
			type: 'GET',
			url: 'stats2.php',
			contentType: "application/json; charset=utf-8", 
			dataType: "json",
			timeout: 2000,
			success: function(data) {
				ret = data;
			},
	});
	$.jqplot.config.enablePlugins = true;
	var plot1 = $.jqplot('osChart',[ret], {
		title: 'mmmmm pie',
		//dataRenderer: ajaxDataRenderer,
		//dataRendererOptions: { unusedOptionalUrl: jsonurl },
		animate: true,
		seriesDefaults : { shadow: true, renderer: $.jqplot.PieRenderer, rendererOptions: { startAngle: 180, highlightMouseOver: true, padding: 4, sliceMargin: 2, showDataLabels: true }},
		highlighter: { show: true, useAxesFormatters: false, tooltipLocation: 'n', tooltipAxes: 'pieref' , formatString: '%s (%p)'},
		legend: { show:true, location: 'e'}
	});
	plot1.replot();
}

var App = {
    /**
     * The onReady method, init everything
     */
    ready: function () {
        this.watchAdminControls();
		$('[data-toggle=tooltip]').tooltip();
		$(".carousel").carousel({interval: false});
		this.watchNavBar();
		this.updateStatsData();
    },

	/*
		Update stats data
	*/
	
	updateStatsData:function() {
		setInterval(function() {
			$.ajax({
				type: 'GET',
				url: 'stats_data.php',
				timeout: 2000,
				data:  ({mode: "cpu"}),
				success: function(data) {
					$("#cpu_load").html(data);
				},
				error: function(request, status, error) {
					$("#mBox div.modal-body").html("<p class='text-primary'>An error occured when attempting to communicate with the server error message: "+request.responseText);
				}
			});
			$.ajax({
				type: 'GET',
				url: 'stats_data.php',
				timeout: 2000,
				data:  ({mode: "disk"}),
				success: function(data) {
					$("#disk_status").html(data);
				},
				error: function(request, status, error) {
					$("#mBox div.modal-body").html("<p class='text-primary'>An error occured when attempting to communicate with the server error message: "+request.responseText);
				}
			});
			$.ajax({
				type: 'GET',
				url: 'stats_data.php',
				timeout: 2000,
				data:  ({mode: "ram"}),
				success: function(data) {
					$("#ram_status").html(data);
				},
				error: function(request, status, error) {
					$("#mBox div.modal-body").html("<p class='text-primary'>An error occured when attempting to communicate with the server error message: "+request.responseText);
				}
			});
			$.ajax({
				type: 'GET',
				url: 'stats_data.php',
				timeout: 2000,
				data:  ({mode: "services"}),
				success: function(data) {
					$("#services").html(data);
				},
				error: function(request, status, error) {
					$("#mBox div.modal-body").html("<p class='text-primary'>An error occured when attempting to communicate with the server error message: "+request.responseText);
				}
			});		
			$.ajax({
				type: 'GET',
				url: 'stats_data.php',
				timeout: 2000,
				data:  ({mode: "mirrors"}),
				success: function(data) {
					$("#mirrors").html(data);
				},
				error: function(request, status, error) {
					$("#mBox div.modal-body").html("<p class='text-primary'>An error occured when attempting to communicate with the server error message: "+request.responseText);
				}
			});				
			var d = new Date();
			$("#timestamp div.container p.navbar-text").html("<em><strong>Refreshed at "+d.toString()+" </strong></em>");
		},3000);
	},
	
	/*
	Monitor Nav bar clicks
	*/
	
	watchNavBar: function () {
	/*
		var ret = null;
		$.ajax({
				async: false,
				type: 'GET',
				url: 'stats2.php',
				contentType: "application/json; charset=utf-8", 
				dataType: "json",
				timeout: 2000,
				success: function(data) {
					ret = data;
				},
		});
		$.jqplot.config.enablePlugins = true;
		var plot1 = $.jqplot('osChart',[ret], {
			title: 'mmmmm pie',
			//dataRenderer: ajaxDataRenderer,
			//dataRendererOptions: { unusedOptionalUrl: jsonurl },
			animate: true,
			seriesDefaults : { shadow: true, renderer: $.jqplot.PieRenderer, rendererOptions: { startAngle: 180, highlightMouseOver: true, padding: 4, sliceMargin: 2, showDataLabels: true }},
			highlighter: { show: true, useAxesFormatters: false, tooltipLocation: 'n', tooltipAxes: 'pieref' , formatString: '%s (%p)'},
			legend: { show:true, location: 'e'}
		});	
	*/
		$('#add').click(function() {
			$('#mainCarousel').carousel(0);
			$('#add').addClass('active');
			$('#remove').removeClass('active');
			$('#modify').removeClass('active');
			$('#templates').removeClass('active');
			$('#help').removeClass('active');
			$('#control').removeClass('active');
			$('#stats').removeClass('active');
			$('#status').removeClass('active');
		});
		$('#remove').click(function() {
			$('#mainCarousel').carousel(1);
			$('#add').removeClass('active');
			$('#remove').addClass('active');
			$('#modify').removeClass('active');
			$('#templates').removeClass('active');
			$('#help').removeClass('active');
			$('#control').removeClass('active');
			$('#stats').removeClass('active');
			$('#status').removeClass('active');
		});
		$('#modify').click(function() {
			$('#mainCarousel').carousel(2);
			$('#add').removeClass('active');
			$('#remove').removeClass('active');
			$('#modify').addClass('active');			
			$('#templates').removeClass('active');
			$('#help').removeClass('active');
			$('#control').removeClass('active');
			$('#stats').removeClass('active');
			$('#status').removeClass('active');
		});
		$('#templates').click(function() {
			$('#mainCarousel').carousel(3);
			$('#add').removeClass('active');
			$('#remove').removeClass('active');
			$('#modify').removeClass('active');			
			$('#templates').addClass('active');
			$('#help').removeClass('active');
			$('#control').removeClass('active');
			$('#stats').removeClass('active');
			$('#status').removeClass('active');
		});		
		$('#control').click(function() {
			$('#mainCarousel').carousel(4);
			$('#add').removeClass('active');
			$('#remove').removeClass('active');
			$('#modify').removeClass('active');			
			$('#templates').removeClass('active');
			$('#help').removeClass('active');
			$('#control').addClass('active');
			$('#stats').removeClass('active');
			$('#status').removeClass('active');
		});				
		$('#help').click(function() {
			$('#mainCarousel').carousel(5);
			$('#add').removeClass('active');
			$('#remove').removeClass('active');
			$('#modify').removeClass('active');			
			$('#templates').removeClass('active');
			$('#help').addClass('active');
			$('#control').removeClass('active');
			$('#stats').removeClass('active');
			$('#status').removeClass('active');
		});				
		$('#stats').click(function() {
			$('#mainCarousel').carousel(6);
			$('#add').removeClass('active');
			$('#remove').removeClass('active');
			$('#modify').removeClass('active');			
			$('#templates').removeClass('active');
			$('#help').removeClass('active');
			$('#control').removeClass('active');
			$('#stats').addClass('active');
			$('#status').removeClass('active');
			refreshPie();
		});		
		$('#status').click(function() {
			$('#mainCarousel').carousel(7);
			$('#add').removeClass('active');
			$('#remove').removeClass('active');
			$('#modify').removeClass('active');			
			$('#templates').removeClass('active');
			$('#help').removeClass('active');
			$('#control').removeClass('active');
			$('#stats').removeClass('active');
			$('#status').addClass('active');
		});			
	},
  
    /**
     * Monitor the Required Variables form for submittal
     */
    watchAdminControls: function () {
		$('#isobtn').click(function(ev) {
			ev.preventDefault();
			$("#mBox div.modal-body").html("<div id=\"ajaxloader3\"><div class=\"outer\"></div><div class=\"inner\"></div><p class=\"text-primary text-center\">Loading...</p></div>");
			setTimeout(function() {
				$.ajax({
					type: 'GET',
					url: 'stats_data.php',
					timeout: 2000,
					data:  ({mode: "isos"}),
					success: function(data) {
						$("#mBox div.modal-body").html(data);
					},
				});				
			}, 3500);
		});
		$('#controlForm').submit(function(ev) {
			ev.preventDefault();
			$.post("admin.php",$('#controlForm').serialize());
			$("#alertHolder").append($("#alertTemplate").html());	
			$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-info-sign'></span> Data submitted");
		});	
		$('#panel').change(function() {
			var id = $('#panel').val();
			if (id > 0)
			{
				$.ajax({
					type: 'POST',
					data:  ({mode: "script",id : id}),
					url: 'db_lib.php',
					success: function(data) {
							var array = data.split("|");
							$("#pname").val(array[0]);
							$("#pscript").val(array[1]);
					}
				});				
			}	
			else
			{
				$('#pname').val("");
				$('#pscript').val("");
			}
		});			
		$('#templateForm').submit(function(ev) {
			ev.preventDefault();
			$.post("admin.php",$('#templateForm').serialize());
			$("#alertHolder").append($("#alertTemplate").html());	
			$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-info-sign'></span> Data submitted");
		});
		$('#tflavor').change(function() {
			var id = $('#tflavor').val();
			if (id > 0)
			{
				$('#tflavorname').val($('#tflavor option:selected').text());
				$.ajax({
					type: 'POST',
					data:  ({mode: "template",id : id}),
					url: 'db_lib.php',
					success: function(data) {
							var array = data.split("|");
							$("#ttemplate").val(array[0]);
							$("#tconfig").val(array[1]);
							$("#tuefi").val(array[2]);
							if (array[3] == 1) { $("#tuse_ks").prop('checked',true); }
							else { $('#tuse_ks').prop('checked',false); }
							
							if (array[4] == 1) { $("#tuse_preseed").prop('checked',true); }
							else { $('#tuse_preseed').prop('checked',false); }
							
							if (array[5] == 1) { $("#tuse_bsd").prop('checked',true); }
							else { $('#tuse_bsd').prop('checked',false); }
							
							if (array[6] == 1) { $("#tuse_xen").prop('checked',true); }
							else { $('#tuse_xen').prop('checked',false); }
							
							if (array[7] == 1) { $("#tuse_unattended").prop('checked',true); }
							else { $('#tuse_unattended').prop('checked',false); }							
					}
				});				
			}
			else
			{
				$('#tflavorname').val("");
				$('#ttemplate').val("");
				$('#tconfig').val("");
				$('#tuefi').val("");
				$('#tuse_ks').prop('checked',false);
				$('#tuse_preseed').prop('checked',false); 
				$('#tuse_bsd').prop('checked',false); 
				$('#tuse_xen').prop('checked',false); 
			}
		});
		$('#modclone').click(function(ev) {
			ev.preventDefault();
			var id = $('#modOS').val();
			$("#alertHolder").append($("#alertTemplate").html());	
			$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');			
			$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ok'></span> Entry cloned, Please refresh the page");
			$.ajax({
				type: 'POST',
				data:  ({mode: "clone",id : id}),
				url: 'db_lib.php'
			});
			location.reload(true);
		});
		$('#modifyForm').submit(function(ev) {
			ev.preventDefault();
			$("#alertHolder").append($("#alertTemplate").html());	
			$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');			
			$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-info-sign'></span> Data submitted");
			$.post("admin.php",$("#modifyForm").serialize());		
		});
		$('#modOS').change(function() {
			var id = $('#modOS').val();
			var flav = 0;
			if (id > 0)
			{
				$.ajax({
					type: 'POST',
					data:  ({mode: "info",id : id}),
					url: 'db_lib.php',
					success: function(data) {
							var array = data.split("|");
							$("#modosname").val(array[0]);
							$("#modosver").val(array[1]);
							if (array[2] == 1) { $("#mod_can_plesk").prop('checked',true); }
							else { $("#mod_can_plesk").prop('checked',false); }
							if (array[3] == 1) { $("#mod_can_cpanel").prop('checked',true); }
							else { $("#mod_can_cpanel").prop('checked',false); }
							$("#modflavor").val(array[4]);
							var modflav = $("#modflavor option:selected").text();
							var isWindows = new RegExp("windows").test(modflav);
							if (isWindows == true) { $("#modwimlocation").prop('disabled',false); }
							else { $("#modwimlocation").prop('disabled',true); }
							$("#modoscomment").val(array[5]);
							$("#modwimlocation").val(array[6]);
					}
				});
			}
		});
		$("#mRemove").click(function() {
			var sOS = $('#os option:selected').text();
			$("#mConfirm div.modal-body p.message").html("<p>Be advised this will remove <b> "+sOS+" </b> from the PXE Installer</p>");
			$("#mConfirm").modal('show');
		});
		$("#mRestore").click(function(ev) {
			ev.preventDefault();
			var restoreID = $("#r_os option:selected").val();
			
			$.ajax({
				type: 'POST',
				data: ({mode: 'restore',id: restoreID}),
				url: 'admin.php',
				success: function(data) {
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');			
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-floppy-saved'></span> " + data);								
				}
			});
			var rText = $("#r_os option:selected").text();
			var rVal = $("#r_os option:selected").val();
			$("#os").append('<option value="' + rVal +'">' + rText + '</option>');			
			$("#r_os option:selected").remove();
		});		
		$("#mForceDel").click(function(ev) {
			ev.preventDefault();
			var removeID = $("#r_os option:selected").val();
			
			$.ajax({
				type: 'POST',
				data: ({mode: 'force',id: removeID}),
				url: 'admin.php',
				success: function(data) {
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');			
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-floppy-remove'></span> " + data);								
				}
			});
			
			$("#r_os option:selected").remove();
		});
		$('#removeForm').submit(function(ev) {
			ev.preventDefault();
			$('#mConfirm').modal('hide');
			$("#alertHolder").append($("#alertTemplate").html());
			$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-info');			
            $("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-trash'></span> OS Moved to Trash.");			
			$.post("admin.php",$("#removeForm").serialize());
			var rText = $("#os option:selected").text();
			var rVal = $("#os option:selected").val();
			$("#r_os").append('<option value="' + rVal +'">' + rText + '</option>');
			$('#os option:selected').remove();
		});
		$('#flavor').change(function() {
			var flav = $('#flavor').val();
			if (flav > 0) 
			{ 
				$('#flavorname').prop('disabled', true); 
				$('#template').prop('disabled', true);
				$('#config').prop('disabled', true);
				$('#flavorname').val($('#flavor option:selected').text());
				$.ajax({
					type: 'POST',
					data:  ({mode: "template",id : flav}),
					url: 'db_lib.php',
					success: function(data) {
							var array = data.split("|");
							$("#template").val(array[0]);
							$("#config").val(array[1]);
					}
				});	
			}
			else 
			{ 
				$('#flavorname').prop('disabled',false); 
				$('#template').prop('disabled', false);
				$('#config').prop('disabled', false);
				$('#flavorname').val("");
				$('#template').val("");
				$('#config').val("");
			}
		});
		$('#uploadForm').ajaxForm({
				beforeSubmit: function() {
					$(this).addClass('loading');
					$('#uploadStatus').html($('#progressbar').html());
					$("#submit").prop('disabled',true);
				},
				uploadProgress: function ( event, position, total, percentComplete ) {
					if (percentComplete == 100) {
						var url = $('#osurl').val();
						if (url == "") { $('#progressBar').css('width',percentComplete+'%').html('Processing...'); }
						else { $('#progressBar').css('width',percentComplete+'%').html('Downloading...'); }
						$("#submit").prop('disabled',false);
					} else {
						$('#progressBar').css('width',percentComplete+'%').html(percentComplete+'%');
					}
				},
				success : function (response) {
					$("#progressOverlay div.progress").removeClass("active");
					$("#progressBar").html('Complete');
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ok-circle'></span> Data submitted: "+response);
					$('html, body').animate({scrollTop:0}, 'fast');
					
				},
				error: function(response) {
					$("#progressOverlay div.progress").removeClass("active").addClass("progress-danger");
					$("#progressBar").html('Error');
					$("#alertHolder").append($("#alertTemplate").html());
					$("#alertHolder div.alert").last().removeClass("alert-success").addClass('alert-error');
					$("#alertHolder div.alert span.message").last().html("<span class='glyphicon glyphicon-ban-circle'></span> An error occurred while uploading the file.<br>"+response);
					$('html, body').animate({scrollTop:0}, 'fast');
				},
			});
	},
 };

/**
 * Start our application
 */
$(document).ready(App.ready.bind(App));
