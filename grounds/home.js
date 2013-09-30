$(function() {
	$("#loginForm").submit(function() {
		$.ajax({
			url: "homeLogin.php",
			data: {
				name: $("#name").val(),
				password: $("#password").val()
			},
			complete: function(xhr, text) {
				switch (xhr.status) {
				case 200:
					window.location = "lobby.php";
					break;
					
				default:
					alert(xhr.responseText + " (Error " + xhr.status + ")");
					break;
				}
			}
		});
		return false;
	});
});
