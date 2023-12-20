import Timer from "easytimer.js";

(function($) {
	$(document).ready(function () {
		let $timers = $('.cp-live-countdown');
		
		if ( ! $timers.length ) {
			return;
		}
		
		$timers.each(function() {
			let $this = $(this);
			let startTime = $this.attr('data-start-time' );

			startTime = parseInt(startTime);

			if ( isNaN(startTime) || ! startTime ) {
				return;
			}
			
			let timer = new Timer();
			timer.start({ countdown: true, startValues: {seconds: startTime}});

			timer.addEventListener('secondsUpdated', function (e) {
				$this.find('.days').html(timer.getTimeValues().days);
				$this.find('.hours').html(timer.getTimeValues().hours);
				$this.find('.minutes').html(timer.getTimeValues().minutes);
				$this.find('.seconds').html(timer.getTimeValues().seconds);
			});
		});
	});
})(jQuery);