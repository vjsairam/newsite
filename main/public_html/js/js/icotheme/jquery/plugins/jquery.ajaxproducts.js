function quickview(e){
    jQuery.colorbox({
        iframe: true,
        href: jQuery(e).attr('data-url'),
        opacity:	0.5,
        speed:		300,
        current:	'{current} / {total}',
        close:      "close",
        innerWidth:'800px',
        innerHeight:'600px',
        onOpen: function(){
            jQuery('#cboxLoadingGraphic').addClass('box-loading');
        },
        onComplete: function(){
            setTimeout(function(){
                jQuery('#cboxLoadingGraphic').removeClass('box-loading');
            },2300);
        }
    });
}
function initAjax(data){
    if(data.status == 'ERROR'){
        ajaxPopup(data.message);
    }else{
        jQuery('.header-maincart').html('');
        if(jQuery('.header-maincart')){
            jQuery('.header-maincart').append(data.output);
            scrollbar();
        }
        ajaxPopup(data.cartbox,false);
    }
}
function scrollbar(){
    jQuery(".header-maincart .cart-content").mCustomScrollbar({
        scrollInertia:150,
        scrollButtons:{
            enable:true
        }
    });
}
function ajaxPopup(layout, cond){
    var time = 5;
    jQuery('.popup-wrapper').remove();
    main = jQuery('<div/>').addClass('popup-wrapper');
    content = jQuery('<div/>').addClass('content-wrapper').appendTo(main);
    elload = jQuery('<div/>').addClass('loading').appendTo(main);
    main.appendTo(jQuery('body'));
    if(cond){
        elload.show();
        content.hide();
    }else{
        content.html(layout);
        content.find('.btn-continue span span').append('<span class="count" data-count="'+time+'">('+time+')</span>');
        var counter = content.find('.btn-continue span span span.count');
        var countDown = function(){
            var time = counter.attr('data-count');
            if (time<= 0) return;
            else time-=1;
            counter.html('('+time+')').attr('data-count', time);
            setTimeout(function(){ countDown() }, 1000);
        }
        countDown();
        content.show();
        elload.hide();
    }
    if(!cond){
        setTimeout(function() {
            main.fadeOut(500);
        }, time*1000);
    }
}
function setBoxLocation(url){
    return window.location.href=url;
}
function boxContinue(){
    jQuery('.popup-wrapper').fadeOut('slow', function() {
        jQuery(this).remove();
    });
}
function setLocation(url)
{
    var checkUrl = (url.indexOf('checkout/cart') > -1);
    if(checkUrl && frontendData.enableAjax){
        data = '&isAjax=1&qty=1';
        url = url.replace("checkout/cart","ajaxproducts/index");
        ajaxPopup(null,true);
        try {
            jQuery.ajax( {
                url : url,
                dataType : 'json',
                data: data,
                type: 'post',
                success : function(data) {
                    initAjax(data);
                }
            });
        } catch (e) {
        }
        return false;
    }
    return window.location.href=url;
}
function setLocationCache(url){
    var checkUrl = (url.indexOf('checkout/cart') > -1);
    if(checkUrl && frontendData.enableAjax){
        data = '&isAjax=1&qty=1';
        url = url.replace("checkout/cart","ajaxproducts/index");
        ajaxPopup(null,true);
        try {
            jQuery.ajax( {
                url : url,
                dataType : 'json',
                data: data,
                type: 'post',
                success : function(data) {
                    initAjax(data);
                }
            });
        } catch (e) {
        }
        return false;
    }
    return window.location.href=url;
}
jQuery(window).load(function(){
    if(jQuery('.product-view').length>0 && frontendData.enableAjax){
        productAddToCartForm.submit = function(button, url) {
            if (this.validator.validate()) {
                var form = this.form;
                var oldUrl = form.action;
                if (url) {
                    form.action = url;
                }
                var e = null;
                if (!url) {
                    url = jQuery('#product_addtocart_form').attr('action');
                }
                var checkWishlistUrl = (url.indexOf('wishlist/index/cart') > -1);

                url = url.replace("checkout/cart","ajaxproducts/index");

                var data = jQuery('#product_addtocart_form').serialize();
                data += '&isAjax=1';
                try {
                    if(checkWishlistUrl){
                        this.form.submit();
                    }else{
                        ajaxPopup(null,true);
                        jQuery.ajax( {
                            url : url,
                            dataType : 'json',
                            type : 'post',
                            data : data,
                            success : function(data) {
                                parent.initAjax(data,true);
                                parent.jQuery.colorbox.close();
                                if (button && button != 'undefined') {
                                    button.disabled = false;
                                }
                            }
                        });
                    }
                } catch (e) {
                }
                this.form.action = oldUrl;
                if (e) {
                    throw e;
                }

            }
            return false;
        }.bind(productAddToCartForm);
    }
});


