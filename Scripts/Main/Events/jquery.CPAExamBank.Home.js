
$.CPAEB.pages.home = {

	slider: {
		currentIndex: 4,
		maxIndex: 5,
		lastAction : null
	}
}

$.CPAEB.pages.home.events = function () {

	var self = $.CPAEB.pages.home;

	$("#slides .prev").on('click', function () {

		self.slider.lastAction = new Date();

		self.slider.currentIndex = self.slider.currentIndex == 1 ? self.slider.maxIndex : self.slider.currentIndex - 1;
		self.slider.updatePagination();
		self.slider.updateSlides('left');

	});

	$("#slides .next").on('click', function () {

		self.slider.lastAction = new Date();

		self.slider.currentIndex = self.slider.currentIndex + 1 > self.slider.maxIndex ? 1 : self.slider.currentIndex + 1;
		self.slider.updatePagination();
		self.slider.updateSlides('right');

	});

	$("#slides .pagination li").on('click', function () {

		self.slider.lastAction = new Date();

		if ($(this).hasClass('current')) { return; }
		$("#slides .pagination li").removeClass('current');
		$(this).addClass('current');

		self.slider.currentIndex = $(this).index() + 1;


		self.slider.updateSlides('fade');

	});

	setInterval(function () {
		self.slider.autoSlide();
	}, 4000);

}

$.CPAEB.pages.home.slider.updatePagination = function () {

	$("#slides .pagination li").removeClass('current');
	var liArray = $("#slides .pagination li");

	// array is 0 index, slider is 1 index
	$(liArray[this.currentIndex - 1]).addClass('current');


}

$.CPAEB.pages.home.slider.updateSlides = function (animation) {

	var currentSlideIndex = this.currentIndex - 1;

	var slideArray = $("#slides .slides_control .slide");

	if (animation == 'fade') {
		$("#slides .slides_control .slide:visible").fadeOut(function () {
			$(slideArray[currentSlideIndex]).fadeIn();
		});
	}
	else {

		var showAnimation = animation == 'left' ? 'right' : 'left';

		console.log("Sliding : " + animation);
		$("#slides .slides_control .slide:visible").hide('slide',{direction:animation},600,function () {
			$(slideArray[currentSlideIndex]).show('slide', { direction: showAnimation }, 1000);
		});
	}
	

	

}

$.CPAEB.pages.home.slider.autoSlide = function () {

	var self = $.CPAEB.pages.home;

	// Ignore autoSlide if there's been an action in the last 10 seconds
	if (self.slider.lastAction != null && new Date() - self.slider.lastAction < 10000) { return }

	self.slider.currentIndex = self.slider.currentIndex + 1 > self.slider.maxIndex ? 1 : self.slider.currentIndex + 1;
	self.slider.updatePagination();
	self.slider.updateSlides('right');
}










$.CPAEB.registerEvents($.CPAEB.pages.home.events);


