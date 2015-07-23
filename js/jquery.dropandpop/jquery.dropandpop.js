(function ($) {
	
	$.fn.dropandpop = function(uploadUrl, onComplete, onDrop, onSelect) {
		var $this = this;
		var isUploading = false;
		
		
		/*
		 * Add classes
		 */
		
		this.addClass('dropandpop');
		
		/* 
		 * Add elements
		 */
		
		this.append('<div class="dropandpop-progress-bar"></div>');
		var $fileInput = $('<input type="file" class="dropandpop-file-input" />');
		this.append($fileInput);
		
		
		/*
		 * Functions
		 */
		
		var setUploading = function() {
			isUploading = true;
			$this.addClass('dropandpop-uploading');
			$this.find('.dropandpop-progress-bar').css('left', '-100%').text('');
		};
		
		var unsetUploading =  function() {
			isUploading = false;
			$this.removeClass('dropandpop-uploading');
			$this.find('.dropandpop-progress-bar').css('left', '-100%').text('');
		};
		
		var uploadFiles = function(files) {
			var xhr = new XMLHttpRequest();
			xhr.upload.addEventListener("progress", updateProgressHandler, false);
			xhr.addEventListener("load", transferCompleteHandler, false);
			xhr.addEventListener("error", transferFailedHandler, false);
			xhr.addEventListener("abort", transferCanceledHandler, false);
			xhr.open('POST', uploadUrl, true);
			var formData = new FormData;
			for (var f = 0; f < files.length; f++) {
				formData.append('uploadedFiles[' + f + ']', files[f]);
			}
			xhr.send(formData);
		};
		
		
		/*
		 * Event handlers
		 */
		
		var updateProgressHandler = function(e) {
			var complete = Math.round(e.loaded / e.total * 100);
			var left = complete - 100;
			$this.find('.dropandpop-progress-bar').css('left', left + '%').text(complete + '%');
		};
		
		var transferCompleteHandler = function() {
			if (onComplete) {
				onComplete(this.responseText, this);
			}
			unsetUploading();
		};
		
		var transferFailedHandler = function() {
			unsetUploading();
		};
		
		var transferCanceledHandler = function() {
			unsetUploading();
		};
		
		var changeHandler = function(e) {
			e.preventDefault();
		};
		this.bind('change', changeHandler);
		
		var dropHandler = function(e) {
			e.preventDefault();
//			if (isUploading) return;
			
			$(this).removeClass('dropandpop-drag-over');
			setUploading();
			var files = e.originalEvent.target.files || e.originalEvent.dataTransfer.files;
			if (onDrop) {
				onDrop(files, e);
			}
			uploadFiles(files);
		};
		this.bind('drop', dropHandler);
		
		var dragoverHandler = function(e) {
			e.preventDefault();
			$(this).addClass('dropandpop-drag-over');
		};
		this.bind('dragover', dragoverHandler);
		
		var dragleaveHandler = function(e) {
			e.preventDefault();
			$(this).removeClass('dropandpop-drag-over');
		};
		this.bind('dragleave', dragleaveHandler);
		
		var clickHandler = function(e) {
			var $fileInput = $(this).find('.dropandpop-file-input');
			$fileInput.trigger('click');
		};
		this.bind('mouseup', clickHandler);
		
		var fileSelectHandler = function(e) {
			$(this).removeClass('dropandpop-drag-over');
			setUploading();
			var files = e.originalEvent.target.files || e.originalEvent.dataTransfer.files;
			if (onSelect) {
				onSelect(files, e);
			}
			uploadFiles(files);
		};
		$fileInput.bind('change', fileSelectHandler);
		
		return this;
	};
	
}(jQuery));