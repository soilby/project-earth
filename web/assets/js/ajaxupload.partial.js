var AjaxUploadPartial = (function () {
    
    function AjaxUploadPartial(button, options) {
        
        this.options = {
            url: '/upload.php',
            addHeaders: null,
            chunkSize: 10 * 1024 * 1024,
            progressElement: null,
            onComplete: null,
            onSubmit: null,
            onProgress: null,
        };
        
        $.extend(this.options, options);
        this.file = null;
        this.fileSize = 0;
        this.currentPosition = 0;
        this.id = null;
        
        var that = this;
        var $input = $('<input type="file" />').css({position: 'fixed', top: '-100px'}).change(function () {
            if ((this.files == undefined) || (this.files[0] == undefined)) {
                return false;
            }
            that._uploadFile(this.files[0]);
        }).appendTo('body');
        $(button).click(function () {
            $input.click();
            return false;
        });
    };
    
    AjaxUploadPartial.prototype.setData = function (data) {
        var headers = {};
        for (var i in data) {
            headers['x-ajaxupload-partial-'+i] = data[i];
        }
        $.extend(this.options.addHeaders, headers)
    };
    
    AjaxUploadPartial.prototype._uploadFile = function (file) {
        this.file = file;
        this.fileSize = file.size;
        this.currentPosition = 0;
        this.id = Math.random().toString(16).slice(2);
        if (typeof this.options.onSubmit == 'function') {
            this.options.onSubmit(this.file);
        }
        this._sendChunk();
    };
    
    AjaxUploadPartial.prototype._sendChunk = function() {
        var data = null, that = this, from = that.currentPosition, size = that.options.chunkSize;
        
        if (this.file.slice) { 
            data = this.file.slice(from, from + size);
        } else {
            if (this.file.webkitSlice) {
                data = this.file.webkitSlice(from, from + size);
            } else {
                if (this.file.mozSlice) {
                    data = this.file.mozSlice(from, from + size);
                }
            }
        }
        var ajaxHeaders = {
            'x-ajaxupload-partial-module': 'ajaxupload.partial',
            'x-ajaxupload-partial-id': that.id,
            'x-ajaxupload-partial-size': that.fileSize,
            'x-ajaxupload-partial-position': that.currentPosition,
            'x-ajaxupload-partial-filename': encodeURIComponent(that.file.name)
        };
        $.extend(ajaxHeaders, that.options.addHeaders);
        
        $.ajax({
            url: that.options.url,
            type: "POST",
            enctype: 'multipart/form-data',
            headers: ajaxHeaders,
            processData: false,
            data: data,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                if (!xhr.upload) {
                    return xhr;
                }
                xhr.upload.addEventListener("progress", function (e) {
                    if (e.lengthComputable) {
                        var completed = Math.ceil((that.currentPosition + e.loaded * that.options.chunkSize / e.total) * 100 / that.fileSize);
                        if (completed > 100) {
                            completed = 100;
                        }
                        if (that.options.progressElement) {
                            $(that.options.progressElement).text(completed+'%');
                        }
                        if (typeof that.options.onProgress == 'function') {
                            that.options.onProgress(that.file, completed);
                        }
                    }
                }, false);
                return xhr;
            },
            success: function (resp) {
                var answer = $.parseJSON(resp);
                if (answer.result != 'OK') {
                    that.currentPosition = answer.next;
                    that._sendChunk();
                } else {
                    if (typeof that.options.onComplete == 'function') {
                        that.options.onComplete(that.file, resp);
                    }
                }
            },
            error: function (response) {
                that._sendChunk();
            }
        });
    };
            
    return AjaxUploadPartial;
})();    
