var App = {
    /**
     * The onReady method, init everything
     */
    ready: function () {
        this.watchControls();
    },
	
	watchControls: function () {
		$('#next').click(function(ev) {
			ev.preventDefault();
			$("#installForm").submit();
		});			
		$('#installForm').validate({
			submitHandler: function(form) {
				$.ajax({
					url: $(form).action,
					type: $(form).method,
					data: $(form).serialize(),
					success: function (response) {
						$("#mAlert div.modal-dialog div.modal-content div.modal-body span.message").html("<p>Data submitted: "+response+"</p>" );
						var audio = new Audio('audio/submit.mp3');
						audio.play();
					},					
					error: function (response,status,error) {
						$("#mAlert div.modal-dialog div.modal-content div.modal-body span.message").html("<p>An error occured: <br>"+response.responseText+"</p>" );
						$("#mAlert").modal('hide');
						var audio = new Audio('audio/error.mp3');
						audio.play();						
					},
					beforeSend: function() {
						$("#mAlert div.modal-dialog div.modal-content div.modal-body span.message").html("<p>Processing...</p>" );
						$("#mAlert").modal('show');
						var audio = new Audio('audio/working.mp3');
						audio.play();
					}
				});
			}					
		});
	},
 };	
 
 /**
 * Start our application
 */
$(document).ready(App.ready.bind(App));