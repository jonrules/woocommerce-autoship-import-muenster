jQuery Drop and Pop
===================

jQuery plugin to drag and drop files and upload them to a webpage.


How it works
------------

jQuery.dropandpop(*string* uploadUrl, *callback*(*FileList* files, *event* e) onDrop, *callback*(*object* response, *XMLHttpRequest* xhr) onComplete);

Files will be uploaded asynchronously to uploadUrl when they are dropped on the dropandpop element using the POST method with the content-type multipart/form-data. The onDrop callback is triggered immediately after the files are released by the user. The onComplete callback is triggered after a response is returned from the server.


Example Usage
-------------

```
<div id="drop"></div>
<script>
var uploadUrl = '/file/upload/path';

var onDrop = function(files, e) {
  console.log(files);
};

var onComplete = function(response, xhr) {
  console.log(response);
};

$('#drop').dropandpop(uploadUrl, onDrop, onComplete);
</script>
```
