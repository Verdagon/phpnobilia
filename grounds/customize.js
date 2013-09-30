var chat = undefined;

function Thumbnail(unit) {
	this.unit = unit;
	this.unitViewJelly = undefined;
	
	this.thumbnailContainerJelly = $("#templates .UnitThumbContainer").clone();
	this.thumbnailJelly = $(".UnitThumb", this.thumbnailContainerJelly);
	
	this.populateFromUnit(this.thumbnailJelly);
	
	$("#unitGrid").append(this.thumbnailContainerJelly);
	
	if (this.unit.status == Unit.UNRECRUITED)
		this.thumbnailJelly.addClass("Unrecruited");
	
	this.thumbnailJelly.click(callback(this, "click"));
}

Thumbnail.prototype.click = function() {
	if (this.unit.status == Unit.RECRUITED) {
		this.open();
	}
	else {
		new Modal().
			addContent("Do you want to recruit " + this.unit.name + "? The cost is $" + this.unit.worth + ", and you have $" + player.money).
			confirm(callback(this, function() {
				$.getJSON("customizeRequest.php", {
					unitID: this.unit.id,
					request: "recruit"
				}, callback(this, "handleResponse"));
			})).
			show();
	}
	return false;
}

Thumbnail.prototype.populateFromUnit = function(element) {
	if (element.hasClass("UnitView")) {
		$(".ExtraText", element).
			html("Lorem ipsum turquoise actualization Lorem ipsum turquoise actualization Lorem ipsum turquoise actualization Lorem ipsum turquoise actualization");
		
		$(".ConfirmLink", element).click(callback(this, "close"));
		$(".TrainLink", element).click(callback(this, "train"));
	}
	else {
		$(".Cost", element).html("$" + this.unit.worth);
	}
	
	$(".UnitImage", element).
		attr("src", this.unit.imagePath());
	
	$(".Name", element).html(this.unit.name);
	$(".Str .Value", element).html(this.unit.stats.str);
	$(".Sta .Value", element).html(this.unit.stats.sta);
	$(".Dex .Value", element).html(this.unit.stats.dex);
	$(".Agl .Value", element).html(this.unit.stats.agl);
	$(".Intel .Value", element).html(this.unit.stats.intel);
	$(".Spir .Value", element).html(this.unit.stats.spir);
}

Thumbnail.prototype.awakenUnitView = function() {
	var thumbnailBounds = this.thumbnailJelly.bounds();
	
	this.unitViewJelly = $("#templates .UnitView").clone().
	 addClass("Flying").
	 css("position", "absolute").
	 css(thumbnailBounds);
	
	this.populateFromUnit(this.unitViewJelly);
	
	this.unitViewJelly.prependTo($("#mainColumn"));
}

Thumbnail.prototype.findDestinationBounds = function() {
	var jelly = this.unitViewJelly.clone().
		attr("style", "position: absolute; left: 10%; width: 80%; top: 0; opacity: 0;").
		appendTo($("#mainColumn"));
	var bounds = jelly.bounds();
	jelly.remove();
	return bounds;
}

Thumbnail.prototype.lockdownUnitView = function() {
	this.unitViewJelly.
	 attr("style", "").
	 removeClass("Flying").
	 css({
		marginLeft: "10%",
		width: "80%"
	 });
}

Thumbnail.prototype.unlockUnitView = function(unitViewJelly) {
	this.unitViewJelly.
		attr("style", "").
		addClass("Flying").
		css({
			position: "absolute",
			left: "10%",
			width: "80%"
		});
	
	// Jquery in safari can't animate from 10%. We need to make it to pixels first.
	var realPosition = this.unitViewJelly.position();
	this.unitViewJelly.css(realPosition);
}

Thumbnail.prototype.removeUnitView = function() {
	this.unitViewJelly.remove();
	delete this.unitViewJelly;
}

Thumbnail.prototype.open = function() {
	// Hide gridded thumbnail
	this.thumbnailJelly.css("opacity", 0);
	
	// Make an absolutely-positioned clone of the gridded thumbnail, hovering over it
	this.awakenUnitView();
	
	// Find the bounds of the unit view as it would appear once we start using
	// it, the "target" bounds.
	var expandedBounds = this.findDestinationBounds();
	
	$(".RightButton", this.unitViewJelly).hide();
	$(".AlignmentWheel", this.unitViewJelly).hide();
	$(".ExtraText", this.unitViewJelly).hide();
	$(".UnitImage", this.unitViewJelly).css("marginLeft", "-7%");
	
	var gridJelly = $("#unitGrid");
	
	gridJelly.animate({opacity: 0}, 500);
	
	setTimeout(callback(this, function() {
		this.unitViewJelly.animate(expandedBounds, 400);
		
		$(".UnitImage", this.unitViewJelly).animate({marginLeft: "0%"}, 400);
		
		setTimeout(callback(this, function() {
			gridJelly.hide();
			
			this.lockdownUnitView();
			
			$(".AlignmentWheel", this.unitViewJelly).fadeIn(200);
			$(".RightButton", this.unitViewJelly).fadeIn(200);
			$(".ExtraText", this.unitViewJelly).fadeIn(400);
		}), 500);
	 }), 600);
}

Thumbnail.prototype.close = function() {
	$(".ExtraText", this.unitViewJelly).animate({opacity: 0}, 200);
	$(".AlignmentWheel", this.unitViewJelly).animate({opacity: 0}, 200);
	$(".RightButton", this.unitViewJelly).animate({opacity: 0}, 200);
	
	setTimeout(callback(this, function() {
		this.unlockUnitView();
		
		var gridJelly = $("#unitGrid").show();
		var thumbnailBounds = this.thumbnailJelly.bounds();
		
		$(".UnitImage", this.unitViewJelly).animate({marginLeft: "-7%"}, 400);
		
		this.unitViewJelly.animate(thumbnailBounds, 400);
		
		setTimeout(callback(this, function() {
			gridJelly.animate({opacity: 1}, 500);
			
			setTimeout(callback(this, function() {
				this.removeUnitView();
				
				this.populateFromUnit(this.thumbnailJelly);
				this.thumbnailJelly.css("opacity", "");
			}), 600);
		}), 500);
	}), 250);
	
	return false;
}

Thumbnail.prototype.train = function() {
	var outerThis = this;
	var unit = this.unit;
	
	var content = $("#templates .Trainer").clone();
	var description = $(".Description", content);
	var buttonsContainer = $(".Buttons", content);
	
	var trainingModal = new Modal();
	
	for (var i = 0; i < Training.subclassConstructors.length; i++) {
		(function() {
			var training = Training.subclassConstructors[i];
			$('<a class="LeftButton" href="#"></a>').
				html(training.friendlyName).
				appendTo(buttonsContainer).
				mouseover(function() {
					description.html(training.description);
				}).
				click(function() {
					new Modal().
						addContent("Train in " + training.className + "?").
						addClass("Small").
						confirm(function() {
							$.getJSON("customizeRequest.php", {
								unitID: unit.id,
								request: "train",
								type: training.className
							}, function(changes) {
								trainingModal.close();
								outerThis.handleResponse(changes);
							});
						}).
						show();
				});
		})();
	}
	
	trainingModal.
		addContent(content).
		show();
	
	return false;
}

function Modal() {
	this.jelly = $("#templates .Modal").clone();
	this.contentJelly = $(".ModalWindow", this.jelly);
	this.confirmHandlers = new Array();
}
Modal.prototype.addContent = function(content) {
	this.contentJelly.append(content);
	return this;
}
Modal.prototype.confirm = function(handler) {
	if (typeof(handler) == "function") {
		if (this.confirmHandlers.length == 0) {
			// if this is the first handler, add the confirmlink button.
			$("#templates .ModalConfirmButton").clone().
				prependTo(this.contentJelly).
				click(interceptor(this, "confirm"));
		}
		
		this.confirmHandlers.push(handler);
	}
	else {
		// Call handlers and close.
		this.close();
		for (var i = 0; i < this.confirmHandlers.length; i++)
			this.confirmHandlers[i]();
	}
	
	return this;
}
Modal.prototype.addClass = function(newClass) {
	this.contentJelly.addClass(newClass);
	return this;
}
Modal.prototype.close = function() {
	this.jelly.remove();
	return this;
}
Modal.prototype.show = function() {
	$(".CancelLink", this.contentJelly).click(interceptor(this, "close"));
	
	$("#mainColumn").append(this.jelly);
	
	this.contentJelly.
		css("opacity", 0).
		animate({opacity: 1});
}

function showChangeAlert(kind, message, centerOn, wait) {
	var func = function() {
		$("#templates ." + kind + "ChangeAlert").
			clone().
			html(message).
			centerOn(centerOn).
			animate({top: "-=200px", opacity: 0}, 4000, "swing", function() {
				$(this).remove();
			});
	};
	
	if (wait)
		setTimeout(func, wait);
	else
		func();
}

Thumbnail.prototype.handleResponse = function(changes) {
	var unit = this.unit;
	var player = this.unit.player;
	
	for (var key in changes) {
		var newValue = changes[key];
		switch (key) {
		case "playerMoney":
			var playerMoney = $("#playerMoney");
			playerMoney.html('$' + newValue);
			
			var difference = newValue - player.money;
			var kind = (difference < 0 ? "Bad" : "Good");
			difference = (difference < 0 ? "- $" + -difference : "+ $" + difference);
			showChangeAlert(kind, difference, playerMoney);
			
			player.money = newValue;
			break;
			
		case "newUnit":
			unit.id = 0;
			unit = vtility.polymorph(newValue);
			unit.player.units[unit.id] = unit;
			break;
			
		case "status":
			if (newValue == Unit.RECRUITED) {
				this.thumbnailJelly.removeClass("Unrecruited");
				showChangeAlert("Good", "Recruited!", this.thumbnailJelly);
			}
			else if (newValue == Unit.UNRECRUITED) {
				this.thumbnailJelly.addClass("Unrecruited");
				showChangeAlert("Bad", "Unrecruited!", this.thumbnailJelly);
			}
			else {
				alert("unknown status change");
				return;
			}
			
			unit.status = newValue;
			break;
			
		case "stats":
			var newStats = vtility.polymorph(newValue);
			var unitViewJelly = this.unitViewJelly;
			
			function showStatChange(statClass, newVal, difference, wait) {
				var label = $(".RightLabel." + statClass, unitViewJelly);
				$(".Value", label).html(newVal);
				showChangeAlert("Good", statClass + " +" + difference, label, wait);
			}
			
			var wait = 0;
			var difference = newStats.subtract(this.unit.stats);
			for (var stat in Stats.classNames)
				if (difference[stat])
					showStatChange(Stats.classNames[stat], newStats[stat], difference[stat], wait += 500);
			
			this.unit.stats = newStats;
			
			break;
			
		case "HunterTraining":
		case "StrengthTraining":
		case "StaminaTraining":
		case "HistoricalTraining":
		case "ArcheryTraining":
		case "SwordplayTraining":
		case "DefensiveTraining":
		case "StrategeryTraining":
		case "ManaTraining":
			var training = vtility.polymorph(newValue);
			unit.training[key] = training;
			showChangeAlert("Good", "+ " + training.constructor.friendlyName, $(".UnitImage", this.unitViewJelly));
			break;
			
		default:
			alert("unknown unit update:" + key);
			break;
		}
	}
}

$(function() {
	var thumbnails = new Array();
	for (var i = 0; i < player.units.length; i++)
		thumbnails.push(new Thumbnail(player.units[i]));
	
	$("#playerMoney").html('$' + player.money);
	
	launchCommunications();
});

function launchCommunications() {
	chat = new Chat($("#rightColumn"));
	
	$().ajaxError(function(event, xhr) {
		alert(xhr.responseText + " (Error " + xhr.status + ")");
	});
	
	$("#leave").click(function() {
		$.get("customizeLeave.php", undefined, function() {
			window.location = "lobby.php";
		});
		return false;
	});
	
	$("#ready").click(function() {
		$.ajax({
			url: "customizeMarkReady.php",
			dataType: "json",
			global: false,
			error: handleError,
			success: handleResponse
		});
		return false;
	});
	
	function update() {
		$.ajax({
			url: "customizeUpdate.php",
			data: { lastChatLineID: chat.lastLineID },
			dataType: "json",
			global: false,
			error: handleError,
			success: handleResponse
		});
	}
	
	function handleError(xhr) {
		if (xhr.status == 403)
			return;
		
		if (xhr.status == 201) {
			window.location = ROOT + "/play/index.php";
			return;
		}
		
		alert(xhr.responseText + " (Error " + xhr.status + ")");
		
		if (xhr.status == 401) {
			window.location = "lobby.php";
		}
	}
	
	function handleResponse(response) {
		if ("chat" in response)
			chat.receive(response.chat);
		
		for (var i = 0; i < response.notifications.length; i++)
			vtility.polymorph(response.notifications[i]).display();
		
		setTimeout(update, 5000);
	}
	
	update();
}