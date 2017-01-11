var SxmFileManagerDialog = {
    
    fileList: [],
    path: '',
    ajaxUploader: null,
    
    escapeString : function(str)
    {
        var trans = [];
        for (var i = 0x410; i <= 0x44F; i++) trans[i] = i - 0x350; // А-Яа-я
        trans[0x401] = 0xA8;    // Ё
        trans[0x451] = 0xB8;    // ё
        var ret = [];
        // Составляем массив кодов символов, попутно переводим кириллицу
        for (var i = 0; i < str.length; i++)
        {
            var n = str.charCodeAt(i);
            if (typeof trans[n] != 'undefined') n = trans[n];
            if (n <= 0xFF) ret.push(n);
        }
        return escape(String.fromCharCode.apply(null, ret));
    },
    
    getDocumentId: function ()
    {
	var w = this.getWin();
        tinymce = w.tinymce;
        return 'documentid='+tinymce.activeEditor.settings.sxmProjectDocumentId;
    },
    
    updateFileList: function () 
    {
        $('#fileList').html('');
        if (this.path != '')
        {
            $('#fileList').append('<div class="fileItem"><a class="file folder" href="#" onclick="SxmFileManagerDialog.operationChangePathUp();return false;">..</a></div>')
        }
        for (i in this.fileList)
        {
            if (this.fileList[i].isfile != 0)
            {
                var size = 0;
                if (this.fileList[i].size < 1024) size = this.fileList[i].size+' б'; else
                if (this.fileList[i].size < 1048576) size = Math.round(this.fileList[i].size / 1024)+' кб'; else
                size = Math.round(this.fileList[i].size / 1048576)+' Мб';
                var downurl = '/system/download/mceprojectfile?'+this.getDocumentId()+'&file='+this.fileList[i].fullfileurl;
                var extention = this.fileList[i].name.substr(this.fileList[i].name.lastIndexOf(".") + 1);
                extention = extention.toLowerCase().replace(/[^a-z]/, '');
                $('#fileList').append('<div class="fileItem"><a class="file extention'+extention+'" href="#" onclick="SxmFileManagerDialog.insertIntoEditor(\''+downurl+'\', \''+this.fileList[i].name+' ('+size+')\');return false;">'+this.fileList[i].name+' ('+size+')</a> '+(this.fileList[i].allowdelete != 0 ? '<a href="#" onclick="SxmFileManagerDialog.operationFileDelete(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton delete">&nbsp;</a>' : '')+'<a href="'+downurl+'" class="miniButton download">&nbsp;</a></div>')
            } else
            {
                $('#fileList').append('<div class="fileItem"><a class="file folder" href="#" onclick="SxmFileManagerDialog.operationChangePath(\''+this.fileList[i].fullfileurl+'\');return false;">'+this.fileList[i].name.replace('#', '/')+'</a> '+(this.fileList[i].allowdelete != 0 ? '<a href="#" onclick="SxmFileManagerDialog.operationFileDelete(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton delete">&nbsp;</a>' : '')+'</div>')
            }
        }
        $('#preloader').hide();
    },
    
    operationLoadFileList: function ()
    {
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: this.getDocumentId()+'&path='+this.path,
            error: function(){
                alert('Ошибка обращения к серверу');
            },
            success: function(data){
                var answer = $.parseJSON(data);
                if (answer.result != "OK") alert(answer.message);
                SxmFileManagerDialog.fileList = answer.list;
                SxmFileManagerDialog.updateFileList();
            }
        });	      
    },
    
    operationChangePath: function (filepath)
    {
        this.path = filepath;
        this.ajaxUploader.setData({'path':this.path});
        this.operationLoadFileList();
    },
    
    operationChangePathUp: function ()
    {
        if (this.path.lastIndexOf('/') >= 0) this.path = this.path.substr(0, this.path.lastIndexOf('/')); else this.path = '';
        this.path = this.path.replace(/\/[^\/]+$/, '');
        this.ajaxUploader.setData({'path':this.path});
        this.operationLoadFileList();
    },
    
    operationFileDelete: function (fileurl)
    {
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: "operation=delete&"+this.getDocumentId()+'&file='+fileurl+'&path='+this.path,
            error: function(){
                alert('Ошибка обращения к серверу');
            },
            success: function(data){
                var answer = $.parseJSON(data);
                if (answer.result != "OK") alert(answer.message);
                SxmFileManagerDialog.fileList = answer.list;
                SxmFileManagerDialog.updateFileList();
            }
        });	      
    },
    
    operationCreateFolder: function (name)
    {
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: "operation=folder&"+this.getDocumentId()+'&name='+name+'&path='+this.path,
            error: function(){
                alert('Ошибка обращения к серверу');
            },
            success: function(data){
                var answer = $.parseJSON(data);
                if (answer.result != "OK") alert(answer.message);
                SxmFileManagerDialog.fileList = answer.list;
                SxmFileManagerDialog.updateFileList();
            }
        });	      
    },
    
    insertIntoEditor: function (url, name)
    {
        var w = this.getWin();
        tinymce = w.tinymce;
        tinymce.EditorManager.activeEditor.insertContent('<a href="' + url +'">'+name+'</a>');
        this.close();
    },
    
    getWin : function() 
    {
            return (!window.frameElement && window.dialogArguments) || opener || parent || top;
    },

    close : function() 
    {
            var t = this;
            function close() 
            {
                tinymce.EditorManager.activeEditor.windowManager.close(window);
                tinymce = tinyMCE = t.editor = t.params = t.dom = t.dom.doc = null; // Cleanup
            };
            if (tinymce.isOpera) this.getWin().setTimeout(close, 0);
            else close();
    },
    
    setAjaxUploader : function (exemp)
    {
        this.ajaxUploader = exemp;
    }
    
};

$(function () 
{
    SxmFileManagerDialog.operationLoadFileList()
    var alaxuploader = new AjaxUpload('#fileButton', 
    {
        action: '/system/download/mceprojectfilelist?operation=upload&'+SxmFileManagerDialog.getDocumentId(),
        name: 'file',
        data: {'path': SxmFileManagerDialog.path},
        onSubmit: function(file, extension)
        {
            $('#preloader').show();
        },
        onComplete: function(file, response)
        {
            var answer = $.parseJSON(response);
            if (answer.result != "OK") alert(answer.message);
            SxmFileManagerDialog.fileList = answer.list;
            SxmFileManagerDialog.updateFileList();
        }
    });
    SxmFileManagerDialog.setAjaxUploader(alaxuploader);
    
    $('#folderButton').click(function () 
    {
        var nameen=prompt("Введите название новой папки (english)", "New folder");
        var name=prompt("Введите название новой папки (русский)", "Новая папка");
        if ((nameen != '') || (name != '')) {
            name = nameen+'#'+name;
        } else {
            name = nameen+name;
        }
        if ((name != null) && (name != '')) {
            SxmFileManagerDialog.operationCreateFolder(name);
        }
        return false;
    });
});
