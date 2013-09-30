var chat = undefined;

$(function() {
	chat = new Chat($("#rightColumn"));
	
	$().ajaxError(function(event, xhr) {
		alert(xhr.responseText + " (Error " + xhr.status + ")");
	});
	
	$("#logout").click(function() {
		$.get("lobbyLogout.php", undefined, function() {
			window.location = "home.php";
		});
		return false;
	});
	
	$("#createGameForm").submit(function() {
		var gameName = $("#gameName").val();
		$.get("lobbyCreateGame.php", {"gameName": gameName}, function(gameID) {
			window.location = "parley.php";
		});
		return false;
	});
	
	function update() {
		$.getJSON("lobbyUpdate.php", {
			lastChatLineID: chat.lastLineID
		}, function(response) {
			chat.receive(response.chat);
			
			for (var i = 0; i < response.notifications.length; i++)
				vtility.polymorph(response.notifications[i]).display();
			
			var games = response.games;
			
			if (games.length) {
				$("#emptyList").hide();
				$("#gamesList").empty().show();
				
				for (var i = 0; i < games.length; i++) {
					var id = games[i].id;
					var name = games[i].name;
					var players = games[i].players;
					
					var html = '<a class="GameListing" href="#">';
					html += '(' + id + ') ' + name + ': ';
					
					for (var j = 0; j < players.length; j++)
						html += players[j].name
						
					html += '</a>';
					
					var jelly = $(html);
					jelly.click(function() {
						$.get("lobbyJoinGame.php", {"gameID": id}, function() {
							window.location = "parley.php";
						});
					});
					
					$("#gamesList").append(jelly);
				}
			}
			else {
				$("#emptyList").show();
				$("#gamesList").hide();
			}
				
			setTimeout(update, 5000);
		});
	}
	
	update();
});
