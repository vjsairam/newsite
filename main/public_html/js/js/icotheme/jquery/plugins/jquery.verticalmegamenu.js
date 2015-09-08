/**
 *
 * ------------------------------------------------------------------------------
 * @category     MT
 * @package      MT_Themes
 * ------------------------------------------------------------------------------
 * @copyright    Copyright (C) 2014 icotheme.com. All Rights Reserved.
 * @license      GNU General Public License version 2 or later;
 * @author       icotheme.com
 * @email        support@icotheme.com
 * ------------------------------------------------------------------------------
 *
 */
(function($){
    $.fn.vmegamenu = function(options) {
        options = $.extend({
            animation: "show",
            direction: "vertical",
            mm_timeout: 100
        }, options);
        var $vmegamenu_object = this;
        $vmegamenu_object.find("li.parent").each(function(){
            var $mm_item = $(this).children('div.sub-wrapper');
            $mm_item.hide();
            $mm_item.wrapInner('<div class="mm-item-base clearfix"></div>');
            var $timer = 0;
            $(this).bind('mouseenter', function(e){
                var mm_item_obj = $(this).children('div.sub-wrapper');
                var mwidth = mm_item_obj.width();
                var mheight = mm_item_obj.height();
                var mtop =$(this).find("a:first").position().top;
                $(this).find("a:first").addClass('hover'); 
                if($(this).parents('.col-right').length){
                    mm_item_obj.css({position: "absolute", right: $(this).parents('.col-right').width(), top: mtop});
                } 
                if($(this).parents('.col-left').length){
                    mm_item_obj.css({position: "absolute", left: $(this).parents('.col-left').width(), top: mtop});
                }      
                clearTimeout($timer);                 
                $timer = setTimeout(function(){
                    switch(options.animation) {
                        case "show":
                            mm_item_obj.show().addClass("shown-sub");
                            break; 
                        case "slide":
                            mm_item_obj.height("auto");
                            mm_item_obj.slideDown('fast', function(){
                                mm_item_obj.css("overflow","inherit");
                            }).addClass("shown-sub");
                        case "slideWidth":
                            mm_item_obj.css({
                                width: 0, 
                                height: 0,
                                display: "block",
                                opacity: 0
                            });  
                            mm_item_obj.animate({
                                width: mwidth,  
                                height: mheight,
                                opacity: 1
                            }, 300, function(){
                                mm_item_obj.css("overflow","inherit");
                            });
                            break;
                        case "fade":
                            mm_item_obj.fadeTo('fast', 1).addClass("shown-sub");
                            break;
                    }                     
                }, options.mm_timeout);
            });
            $(this).bind('mouseleave', function(e){
                clearTimeout($timer);
                var mm_item_obj = $(this).children('div.sub-wrapper');
                $(this).find("a:first").removeClass('hover'); 
                switch(options.animation) {
                    case "show":
                        mm_item_obj.hide();
                        break; 
                    case "slide":
                        mm_item_obj.slideUp( 'fast',  function() {});
                        break;
                    case "slideWidth": 
                        mm_item_obj.hide();                         break;
                    case "fade":
                        mm_item_obj.fadeOut( 'fast', function() {});
                        break;
                }                 
            });
        });
        this.show();
    };
})(jQuery);
