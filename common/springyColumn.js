function fastAnimate(numSteps, timeBetween, callback) {
	for (var volatileStep = 0; volatileStep < numSteps; volatileStep++) {
		(function() {
			var step = volatileStep;
			setTimeout(function() {
				callback(step);
			}, step * timeBetween);
		})();
	}
}

function springLoadColumn(side) {
	vtility.assert(side == "Left" || side == "Right");
	var lowerSide = side.toLowerCase();
	
	function transitionColumn(targetWidth) {
		var container = $("#" + lowerSide + "ColumnContainer");
		var mainColumn = $("#mainColumnContainer");
		
		var startingWidth = container.width();
		
		fastAnimate(20, 30, function(step) {
			var percentageRoot = 1 - step * 0.05;
			var percentage = percentageRoot * percentageRoot; // Squaring the percentage for a fast-then-slow effect.
			var newWidth = targetWidth * (1 - percentage) + percentage * startingWidth;
			
			container.css("width", 5 + newWidth + "px");
			mainColumn.css("margin" + side, 20 + newWidth + "px");
		});
	}

	var columnWidth = $("#" + lowerSide + "ColumnContainer").width();
	
	var collapseFunction = function() {
		transitionColumn(0);
		
		if (side == "Left") {
			$("#leftStarky").animate({opacity: 0}, 600, "swing", function() {
				$(this).hide();
			});
			
			$("#leftNobilia").animate({
				left: "10px",
				top: "0px",
				width: "160px"
			}, 600, "swing");
		}
		
		$(this).
			unbind("click", collapseFunction).
			bind("click", expandFunction);
		return false;
	};
	
	var expandFunction = function() {
		transitionColumn(columnWidth);
		
		if (side == "Left") {
			$("#leftStarky").show().animate({opacity: 1}, 600, "swing");
			
			$("#leftNobilia").animate({
				left: "193px",
				top: "5px",
				width: "260px"
			}, 600, "swing", function() {
				$(this).attr("style", "");
			});
		}
		
		$(this).
			unbind("click", expandFunction).
			bind("click", collapseFunction);
		return false;
	}
	
	$("#" + lowerSide + "Separator a").click(collapseFunction);
}