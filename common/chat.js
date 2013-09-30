function Chat(chatContainerJelly) {
	this.chatContainerJelly = chatContainerJelly;
	this.logJelly = $(".chatLog", chatContainerJelly);
	this.clockJelly = $(".chatClock", chatContainerJelly);
	
	this.lastLineID = 0;
	
	var outerThis = this;
	function enter(e) {
		if (e.which == 13) {
			self.send($(this).val());
			$(this).val("");
			return false;
		}
	}
	
	var inputJelly = $(".chatInput", chatContainerJelly);
	
	inputJelly.keypress(function(e) {
		if (e.which == 13) {
			if (inputJelly.val() != "") {
				outerThis.send(inputJelly.val());
				inputJelly.val("");
			}
			return false;
		}
	});
	
	$(".chatInputButton", chatContainerJelly).click(function() {
		if (inputJelly.val() != "") {
			outerThis.send(inputJelly.val());
			inputJelly.val("");
		}
		return false;
	});
}

Chat.prototype.send = function(text) {
	var self = this;
	$.getJSON(ROOT + "/common/sendChat.php", {
		lastLineID: this.lastLineID,
		text: text
	}, function(responseJSON) {
		self.receive(responseJSON);
	});
}

Chat.prototype.receive = function(json) {
	this.lastLineID = json.newLastLineID;
	
	if (json.lastChatTime)
		this.clockJelly.html("(Last message at " + json.lastChatTime + ")");
	
	var chats = json.chats;
	for (var i = 0; i < chats.length; i++)
		this.logJelly.append($('<div>' + chats[i].text + '</div>'));
	
	if (chats.length > 0)
		this.chatContainerJelly.attr("scrollTop", this.chatContainerJelly.attr("scrollHeight"));
}