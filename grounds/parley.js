var chat = undefined;

$(function() {
	chat = new Chat($("#rightColumn"));
	
	$().ajaxError(function(event, xhr) {
		alert(xhr.responseText + " (Error " + xhr.status + ")");
	});
	
	$("#leave").click(function() {
		$.get("parleyLeave.php", undefined, function() {
			window.location = "lobby.php";
		});
		return false;
	});
	
	$("#startGame").click(function() {
		$.get("parleyStartGame.php", undefined, function() {
			window.location = "customize.php";
		});
		return false;
	});
	
	function update() {
		$.ajax({
			url: "parleyUpdate.php",
			data: {
				lastChatLineID: chat.lastLineID
			},
			dataType: "json",
			global: false,
			error: function(xhr) {
				switch (xhr.status) {
				case 201:
					window.location = "customize.php";
					return;
				}
				
				alert(xhr.responseText + " (Error " + xhr.status + ")");
				
				switch (xhr.status) {
				case 401:
					window.location = "lobby.php";
					break;
				}
			},
			success: function(response) {
				chat.receive(response.chat);
				
				for (var i = 0; i < response.notifications.length; i++)
					vtility.polymorph(response.notifications[i]).display();
				
				var players = response.game.players;
				
				$("#playerList").empty();
				
				for (var i = 0; i < players.length; i++) {
					var id = players[i].id;
					var name = players[i].name;
					var jelly = $('<div><a href="#">' + name + '</a>, level 10</div>');
					$("a", jelly).click(function() {
						alert("show player information here");
					});
					
					$("#playerList").append(jelly);
				}
				
				setTimeout(update, 5000);
			}
		});
	}
	
	update();
});
