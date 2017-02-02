var AjaxPreloader = {
    delay : 200,
    show_timeout : null,
    init : function() {
        var that = this;
        // Обработчики событий
        $("body").ajaxSend(function(e, xhr, settings) {that.show();});
        $("body").ajaxSuccess(function(e, xhr, settings) {that.hide();});
        $("body").ajaxError(function(e, xhr, settings) {that.hide();});
   },
   show : function() {
        if (this.show_timeout) clearTimeout(this.show_timeout);
        this.show_timeout = setTimeout(function() {
            $('#preloader').show();
        }, this.delay);
   },
   hide : function() {
        clearTimeout(this.show_timeout);
        $('#preloader').hide();
   }
};

$(function () {
        AjaxPreloader.init();
	$('nav ul li:has(ul.secondStage)').hover(function () {$(this).children('ul.secondStage').stop(true,true).fadeIn(200);}, function () {$(this).children('ul.secondStage').stop(true,true).fadeOut(200);});
	$('input, button, a.button, select, textarea').not('.uniformOff').uniform();
        $('select.quickSearch').chosen({no_results_text: 'Нет результатов...', width: '300px'});
        function autoHeight()
        {
		$('.mainContent').animate({'min-height':($(window).height() - 20 - $('.mainHeader').outerHeight(true) - $('.mainFooter').outerHeight(true))+'px'},500);
        }
        $('.mainContent').css('min-height',($(window).height() - 20 - $('.mainHeader').outerHeight(true) - $('.mainFooter').outerHeight(true))+'px');
	setInterval(function () {autoHeight();}, 2000);
        
        $('.helpContainerButton').html('?').click(function () 
        {
            var id = $(this).data('id');
            if ((id == '') || ($('#helpContainer').length == 0) || ($('#helpContainer-'+id).lenght == 0)) {alert('Ошибка');return false;}
            
            var data = $('#helpContainer-'+id).html();
            $('#helpContainer').append('<div class="helpContainerWindow">'+data+'<p class="tac"><a class="button" href="#" onclick="$(\'.helpContainerWindow\').remove();$(\'#helpContainer\').hide();return false;">Закрыть</a></p></div>')
            $('#helpContainer').show();
            $('.helpContainerWindow a.button').uniform();
        });
        
        
        $('#tabsContent>div').css('min-height',($('#tabsButtons').outerHeight() + 20)+'px');
        $('#tabsButtons>a').click(function() 
        {
            var click_id = $(this).attr('id');
            if (click_id != $('#tabsButtons>a.active').attr('id')) 
            {
                $('#tabsButtons>a').removeClass('active');
                $(this).addClass('active');
                $('#tabsContent>div').removeClass('active');
                $('#tabsContent>div#con_' + click_id).addClass('active');
            }
        });
});

function checkAll(that)
{
    if ($(that).is(':checked'))
    {
        $(that).closest('table').find('tr td div.checker span').addClass('checked').children('input').attr('checked','checked');
    } else
    {
        $(that).closest('table').find('tr td div.checker span').removeClass('checked').children('input').removeAttr('checked');
    }
}


function ajaxAction(path, action, formSelector, target)
{
    $.ajax({
        type: "POST",
        url: path,
        data: "action="+action+"&"+$(formSelector).serialize(),
        error: function(){
            alert('Ошибка обращения к серверу');
        },
        success: function(data){
            $(target).html(data);    
        }
    });	      
}

function ajaxActionData(path, action, datain, target)
{
    $.ajax({
        type: "POST",
        url: path,
        data: "action="+action+"&"+datain,
        error: function(){
            alert('Ошибка обращения к серверу');
        },
        success: function(data){
            $(target).html(data);    
        }
    });	      
}

function ajaxActionConfirm(path, action, formSelector, target, query)
{
    if (confirm(query))
    {
        $.ajax({
            type: "POST",
            url: path,
            data: "action="+action+"&"+$(formSelector).serialize(),
            error: function(){
                alert('Ошибка обращения к серверу');
            },
            success: function(data){
                $(target).html(data);    
            }
        });	      
    }
}

function addslashes(string) 
{
    return String(string).replace(/\\/g, '\\\\').
        replace(/\u0008/g, '\\b').
        replace(/\t/g, '\\t').
        replace(/\n/g, '\\n').
        replace(/\f/g, '\\f').
        replace(/\r/g, '\\r').
        replace(/'/g, '\\\'').
        replace(/"/g, '\\"');
}

function htmlentities(string)
{
    return String(string).replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

}