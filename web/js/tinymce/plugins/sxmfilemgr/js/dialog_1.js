var SxmFileManagerDialog = {
    
    fileList: [],
    path: '',
    ajaxUploader: null,
    fileToMove: null,
    
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
        return 'projectid='+w.sxmProjectProjectId;
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
                $('#fileList').append('<div class="fileItem"><a class="file extention'+extention+'" href="#" title="Добавить в мастерскую" onclick="SxmFileManagerDialog.insertIntoEditor(\''+this.fileList[i].fileurl+'\', \''+downurl+'\', \''+this.fileList[i].name+' ('+size+')\');return false;">'+this.fileList[i].name+' ('+size+')</a> '+
                                     (this.fileList[i].allowunlink != 0 ? '<a href="#" title="Отсоединить от мастерской" onclick="SxmFileManagerDialog.operationFileUnlink(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton unlink">&nbsp;</a>' : '')+
                                     (1 != 0 ? '<a href="#" title="Переместить" onclick="SxmFileManagerDialog.operationFileMove(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton move">&nbsp;</a>' : '')+
                                     (this.fileList[i].allowdelete != 0 ? '<a href="#" title="Удалить" onclick="SxmFileManagerDialog.operationFileDelete(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton delete">&nbsp;</a>' : '')+
                                     '<a href="'+downurl+'" title="Скачать" class="miniButton download">&nbsp;</a></div>')
            } else
            {
                $('#fileList').append('<div class="fileItem"><a class="file folder" href="#" onclick="SxmFileManagerDialog.operationChangePath(\''+this.fileList[i].fullfileurl+'\');return false;">'+this.fileList[i].name.replace('#', '/')+'</a> <a href="#" title="Переименовать" onclick="SxmFileManagerDialog.operationFolderRename(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton rename">&nbsp;</a>'+(this.fileList[i].allowdelete != 0 ? '<a href="#" title="Удалить" onclick="SxmFileManagerDialog.operationFileDelete(\''+this.fileList[i].fileurl+'\');return false;" class="miniButton delete">&nbsp;</a>' : '')+'</div>');
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

    operationFileMove: function (fileurl)
    {
        /*$('#preloader').show();
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
        });	      */
        this.fileToMove = (this.path != '' ? this.path+'/' : '')+fileurl;
        $('#moveToThis').show();
    },

    operationFileMoveCommit: function ()
    {
        if (!this.fileToMove) {
            return false;
        }
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: "operation=move&"+this.getDocumentId()+'&file='+this.fileToMove+'&path='+this.path,
            error: function(){
                alert('Ошибка обращения к серверу');
            },
            success: function(data){
                var answer = $.parseJSON(data);
                if (answer.result != "OK") alert(answer.message);
                SxmFileManagerDialog.fileList = answer.list;
                SxmFileManagerDialog.updateFileList();
                this.fileToMove = '';
                $('#moveToThis').hide();
            }
        });
    },

    operationFolderRename: function (fileurl)
    {
        var nameen=prompt("Введите название новой папки (english)", decodeURIComponent(fileurl.replace(/\+/g, ' ')).split('#')[0]);
        if (nameen === null) {
            return false;
        }
        var name=prompt("Введите название новой папки (русский)", decodeURIComponent(fileurl.replace(/\+/g, ' ')).split('#')[1]);
        if (name === null) {
            return false;
        }
        if ((nameen != '') && (name != '')) {
            name = nameen+'#'+name;
        } else {
            name = nameen+name;
        }
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: "operation=folderrename&"+this.getDocumentId()+'&oldname='+fileurl+'&path='+this.path+'&name='+name,
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
        return false;
    },
    
    operationFileUnlink: function (fileurl)
    {
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: "operation=unlink&"+this.getDocumentId()+'&file='+fileurl+'&path='+this.path,
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
    
    insertIntoEditor: function (fileurl, url, name)
    {
        $('#preloader').show();
        $.ajax({
            type: "POST",
            url: '/system/download/mceprojectfilelist',
            data: "operation=addproject&"+this.getDocumentId()+'&file='+fileurl+'&path='+this.path,
            error: function(){
                alert('Ошибка обращения к серверу');
            },
            success: function(data){
                var answer = $.parseJSON(data);
                if (answer.result != "OK") alert(answer.message);
                SxmFileManagerDialog.fileList = answer.list;
                SxmFileManagerDialog.updateFileList();
                var w = SxmFileManagerDialog.getWin();
                w.sxmProjectCloseWindow(url, name);
                if ((answer.result == 'OK') && (w.mirKnowledgeManager != undefined)) {
                    w.mirKnowledgeManager.ajaxMode(name, 1, w.sxmProjectProjectId, decodeURIComponent(SxmFileManagerDialog.path.replace(/\+/g, ' '))+'/'+decodeURIComponent(fileurl.replace(/\+/g, ' ')));
                }
            }
        });	      
    },
    
    getWin : function() 
    {
        return (!window.frameElement && window.dialogArguments) || opener || parent || top;
    },

    setAjaxUploader : function (exemp)
    {
        this.ajaxUploader = exemp;
    }
    
};

$(function () 
{
    SxmFileManagerDialog.operationLoadFileList();
    /*var alaxuploader = new AjaxUpload('#fileButton', 
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
    });*/
    
    var alaxuploader =  new AjaxUploadPartial('#fileButton', {
        url: '/system/download/mceprojectfilelist?operation=upload&'+SxmFileManagerDialog.getDocumentId(),
        addHeaders: {'x-ajaxupload-partial-path': SxmFileManagerDialog.path},
        progressElement: "#progress",
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

    $('#moveToThis').click(function () 
    {
        SxmFileManagerDialog.operationFileMoveCommit();
        return false;
    });    
    
    $('#folderButton').click(function () 
    {
        var nameen=prompt("Введите название новой папки (english)", "New folder");
        if (nameen === null) {
            return false;
        }
        var name=prompt("Введите название новой папки (русский)", "Новая папка");
        if (name === null) {
            return false;
        }
        if ((nameen != '') && (name != '')) {
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
