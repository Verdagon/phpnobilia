$(function() {
	$("#registerForm").submit(function() {
		$.ajax({
			url: "registerCreate.php",
			data: {
				name: $("#name").val(),
				password: $("#password").val(),
				email: $("#email").val()
			},
			complete: function(xhr, text) {
				switch (xhr.status) {
				case 200:
					alert("Your account has been created.");
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
