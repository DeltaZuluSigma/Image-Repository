$(document).ready(function () {
	/* terminal-input Event Listener & Function
       - Returns the command upon 'enter' to be processed
    */
	$('#terminal-input').keyup(function (e) {
    	// Terminal Return Check
		if (e.key == 'Enter') {
        	// Input Processing Variables
			var line = $('#terminal-input').val().toLowerCase();
			var op = line.split(" ", 1)[0];
			var parts = line.substring(op.length + 1);
			
            // Valid Command Check
			if ((op == "login") || (op == "logout") || (op == "display") || (op == "view") || (op == "del")) {
            	// AJAX Post Request
				$.post('verify.php', {operation: op, part: parts}, function (rsp) {
                	// Command Appearance Switch Statement
					switch (op) {
						case "login":
						case "logout":
                        case "del": $('#display').css('text-align','center'); $('#display').html(rsp); break;
						case "display": formatDisplay(rsp); break;
						case "view": formatImg(rsp); break;
					}
				});
			}
			else if (op == "add") {
            	// File Form Function Call
				fileForm(parts);
			}
			else {
				$('#display').html("Invalid command (JS).");
			}
			
            // Terminal/Input Clearing
			$('#terminal-input').val("");
		}
	});
	
    /***** fileForm Function
       - Prompts the user with a form to add an image file
    */
	function fileForm(parts) {
    	// Form Variable
		var upForm = "<form class=\"mx-auto p-1\"><input type=\"file\" id=\"add\" /><label for=\"add\">Add File</label><button id=\"upload\">Upload</button>"+
			"</form><p class=\"disclaimer\">Please upload only one image at a time. Accepted image types include JPG, PNG, TIF, and GIF.</p>";
		$('#display').html(upForm);
		
        // Establish Form Functionality
		$('#upload').on('click', function (e) {
        	// Split image name and permission
        	var comp = parts.split(" ");
            // Retrieve a file from the form
			var file_data = $('#add').prop('files')[0];
			var form_data = new FormData();
			form_data.append('file', file_data);
			
            // Prevent Page Reloading
			e.preventDefault();
			
            // AJAX Request
			$.ajax({
				url: 'verify.php?op=add&name=' + comp[0] + '&perm=' + comp[1],
				dataType: 'text',
				contentType: false,
				processData: false,
				data: form_data,
				type: 'post',
				success: function(rsp) {
					$('#display').html(rsp);
				}
			 });
		});
	}
    
    /***** formatDisplay Function
       - Formats the display section to display results appropriately
    */
    function formatDisplay(rsp) {
    	var names = rsp.split(" ");
        var pub_idx = names.indexOf('public'), pvt_idx = names.indexOf('private');
        
        if ((pub_idx < 0) && (pvt_idx < 0)) {
        	$('#display').css('text-align','center');
            $('#display').html(rsp);
        }
        else {
        	var txt = "", start = Math.min(pub_idx, pvt_idx);
            
            $('#display').css('text-align','left');
            
            // Format Images Loop
            for (var i = start; i < names.length; i++) {
                if (names[i] == "public") { txt += "Public Images:<br>"; }
                else if (names[i] == "private") { txt += "Private Images:<br>"; }
                else {
                    txt += names[i] + "<br>";
                    if (names[i+1] == "private") { txt += "<br>"; }
                }
            }
			
            // Display Results
            $('#display').html(txt);
        }
    }
    
    /***** formatImg Function
       - Produces an image to be displayed in the display section
    */
    function formatImg(rsp) {
    	var cpnt = rsp.split(" "), start = cpnt.indexOf('s');
    	if (start > 0) {
        	start++;
            var h = parseFloat(cpnt[start+1]), w = parseFloat(cpnt[start+2]);
            var hm = h/w, nh;
			
            $('#display').html("<img src='" + cpnt[start] + "' />");
            if (w > 1100) {
            	w = 1100;
                $('img').css('width', '1100px');
                nh = hm * w;
                $('img').css('height', '' + nh + 'px');
            }
        }
        else {
        	$('#display').css('text-align','center');
            $('#display').html(rsp);
        }
    }
});