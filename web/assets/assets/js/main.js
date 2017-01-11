
var earthTools = {
    
    currentLocale: null,
    
    /*
    preloaderDelay : 200,
    preloaderShowTimeout : null,
    
    preloaderShow : function() {
        if (this.preloaderShowTimeout) clearTimeout(this.preloaderShowTimeout);
        this.preloaderShowTimeout = setTimeout(function() {
            $('#ec-preloader').show();
        }, this.preloaderDelay);
    },
    
    preloaderHide : function() {
        clearTimeout(this.preloaderShowTimeout);
        $('#ec-preloader').hide();
    },
    */
    init : function (locale) {
        
        this.currentLocale = locale;
        
        $(function () {
            $('<div id="earth-menu-wrap"></div>').appendTo('body');
            $('#earth-menu-btn').click(function () {
                if ($('#earth-menu').is('.active')) {
                    $('#earth-menu').removeClass('active').animate({width: 0}, 300, function () {$(this).hide();});
                    $('#earth-menu-wrap').hide();
                } else {
                    $('#earth-menu').width(0).show().addClass('active').animate({width: 300}, 300);
                    $('#earth-menu-wrap').show();
                }
                return false;
            });
            $('#earth-menu-wrap').click(function () {
                $('#earth-menu').removeClass('active').animate({width: 0}, 300, function () {$(this).hide();});
                $('#earth-menu-wrap').hide();
            });
            
            earthTools.transliterate();
            earthTools.initModileMenu();
            /*
            
            $(document).ajaxSend(function(e, xhr, settings) {
                gabyTools.preloaderShow();
            });
            $(document).ajaxSuccess(function(e, xhr, settings) {
                gabyTools.preloaderHide();
            });
            $(document).ajaxError(function(e, xhr, settings) {
                gabyTools.preloaderHide();
            });
            */
        });
        
    },
    
    initModileMenu: function () {
        var choices = $('.earth-nav-language').html();
        $('#earth-menu-lang').append('<ul class="earth-menu-lang-mobile">'+choices+'</ul>')
    },
    
    transliterate: function () {
        var transliteration = new Array();
        transliteration['А'] = 'A';        transliteration['а'] = 'a';        transliteration['Б'] = 'B';        transliteration['б'] = 'b';        transliteration['В'] = 'V';
        transliteration['в'] = 'v';        transliteration['Г'] = 'G';        transliteration['г'] = 'g';        transliteration['Д'] = 'D';        transliteration['д'] = 'd';
        transliteration['Е'] = 'E';        transliteration['е'] = 'e';        transliteration['Ё'] = 'Yo';        transliteration['ё'] = 'yo';        transliteration['Ж'] = 'Zh';
        transliteration['ж'] = 'zh';        transliteration['З'] = 'Z';        transliteration['з'] = 'z';        transliteration['И'] = 'I';        transliteration['и'] = 'i';
        transliteration['Й'] = 'J';        transliteration['й'] = 'j';        transliteration['К'] = 'K';        transliteration['к'] = 'k';        transliteration['Л'] = 'L';
        transliteration['л'] = 'l';        transliteration['М'] = 'M';        transliteration['м'] = 'm';        transliteration['Н'] = 'N';        transliteration['н'] = 'n';
        transliteration['О'] = 'O';        transliteration['о'] = 'o';        transliteration['П'] = 'P';        transliteration['п'] = 'p';        transliteration['Р'] = 'R';
        transliteration['р'] = 'r';        transliteration['С'] = 'S';        transliteration['с'] = 's';        transliteration['Т'] = 'T';        transliteration['т'] = 't';
        transliteration['У'] = 'U';        transliteration['у'] = 'u';        transliteration['Ф'] = 'F';        transliteration['ф'] = 'f';        transliteration['Х'] = 'H';
        transliteration['х'] = 'h';        transliteration['Ц'] = 'C';        transliteration['ц'] = 'c';        transliteration['Ч'] = 'Ch';        transliteration['ч'] = 'ch';
        transliteration['Ш'] = 'Sh';        transliteration['ш'] = 'sh';        transliteration['Щ'] = 'Shch';        transliteration['щ'] = 'shch';        transliteration['Ъ'] = '"';
        transliteration['ъ'] = '"';        transliteration['Ы'] = 'Y\'';        transliteration['ы'] = 'y\'';        transliteration['Ь'] = '\'';        transliteration['ь'] = '\'';
        transliteration['Э'] = 'E\'';        transliteration['э'] = 'e\'';        transliteration['Ю'] = 'Yu';        transliteration['ю'] = 'yu';        transliteration['Я'] = 'Ya';
        transliteration['я'] = 'ya';
        $('span.mirclubTransliterationImportant'+(this.currentLocale == 'en' ? ',span.mirclubTransliteration' : '')).each(function () {
            var text = $(this).html();
            var result = '';
            for (var i = 0; i < text.length; i++) {
                if (transliteration[text[i]] != undefined)
                    result += transliteration[text[i]];
                else
                    result += text[i];
            }
            $(this).html(result);
        });
    },
    
    
    alert : function (message, type) {
        sergsxmUIFunctions.alert(message, type, '#earth-alerts');
    },
    
    confirm : function (message, title, okText, cancelText, callBack) {
        sergsxmUIFunctions.confirm(message, title, okText, cancelText, callBack);
    },
/*
            $(function () {
                var transliteration = new Array();
                transliteration['А']='A'; transliteration['а']='a'; transliteration['Б']='B'; transliteration['б']='b'; transliteration['В']='V'; transliteration['в']='v';
                transliteration['Г']='G'; transliteration['г']='g'; transliteration['Д']='D'; transliteration['д']='d'; transliteration['Е']='E'; transliteration['е']='e';
                transliteration['Ё']='Yo'; transliteration['ё']='yo'; transliteration['Ж']='Zh'; transliteration['ж']='zh'; transliteration['З']='Z'; transliteration['з']='z';
                transliteration['И']='I'; transliteration['и']='i'; transliteration['Й']='J'; transliteration['й']='j'; transliteration['К']='K'; transliteration['к']='k';
                transliteration['Л']='L'; transliteration['л']='l'; transliteration['М']='M'; transliteration['м']='m'; transliteration['Н']='N'; transliteration['н']='n';
                transliteration['О']='O'; transliteration['о']='o'; transliteration['П']='P'; transliteration['п']='p'; transliteration['Р']='R'; transliteration['р']='r';
                transliteration['С']='S'; transliteration['с']='s'; transliteration['Т']='T'; transliteration['т']='t'; transliteration['У']='U'; transliteration['у']='u';
                transliteration['Ф']='F'; transliteration['ф']='f'; transliteration['Х']='H'; transliteration['х']='h'; transliteration['Ц']='C'; transliteration['ц']='c';
                transliteration['Ч']='Ch'; transliteration['ч']='ch'; transliteration['Ш']='Sh'; transliteration['ш']='sh'; transliteration['Щ']='Shch'; transliteration['щ']='shch';
                transliteration['Ъ']='"'; transliteration['ъ']='"'; transliteration['Ы']='Y\''; transliteration['ы']='y\''; transliteration['Ь']='\''; transliteration['ь']='\'';
                transliteration['Э']='E\''; transliteration['э']='e\''; transliteration['Ю']='Yu'; transliteration['ю']='yu'; transliteration['Я']='Ya'; transliteration['я']='ya';
                $('span.mirclubTransliterationImportant{% if currentLocale == 'en' %},span.mirclubTransliteration{% endif %}').each(function () {
                    var text = $(this).html();
                    var result = '';
                    for (i = 0; i < text.length; i++) {
                        if (transliteration[text[i]] != undefined) result += transliteration[text[i]]; else result += text[i];
                    }                
                    $(this).html(result);                    
                });
            });
*/

    
};


