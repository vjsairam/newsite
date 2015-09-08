var widgetConfig = {
    id: null,
    config: {},
    init: function(id,config) {
        widgetConfig.id = jQuery('#'+id);
        widgetConfig.config = config;
        widgetConfig.initCountdown();
        widgetConfig.initTab();
        if (widgetConfig.config.carousel && widgetConfig.config.carousel.enable) {
            widgetConfig.initCarousel();
        }
    },
    initCountdown: function(){
        if (!widgetConfig.config.countdown) return;
        if (!widgetConfig.config.countdown.enable) return;
        widgetConfig.id.find('.product-date').each(function(i,item){
            var date = jQuery(item).attr('data-date');
            if(date){
                var config = {date: date};
                Object.extend(config, widgetConfig.config.countdown);
                Object.extend(config, widgetConfig.config.countdownConfig);
                if(widgetConfig.config.countdownTemplate){
                    config.template = widgetConfig.config.countdownTemplate;
                }
                jQuery(item).countdown(config);
            }
        });
    },
    initCarousel: function(){
        if (widgetConfig.config.carousel && widgetConfig.config.carousel.enable) {
            widgetConfig.id.find('.owl-carousel').each(function (i,div) {
                jQuery(div).owlCarousel(widgetConfig.config.carousel);
            });
        }
    },
    initTab: function(){
        if (!widgetConfig.config.requestUrl) return;
        widgetConfig.id.find('.widget-tabs a').each(function(i,tab){
            var tab_content = widgetConfig.id.find(jQuery(tab).attr('href'));
            if (!tab_content) return;
            if (tab_content.find('ul:first').length > 0) {
                tab_content.has_content = true;
            }
            jQuery(tab).on('click', function(e){
                e.preventDefault();
                widgetConfig.hasTab(tab, tab_content);
                if (tab_content.has_content) return;
                var type = jQuery(this).attr('data-type'),
                    value = jQuery(this).attr('data-value'),
                    limit = jQuery(this).attr('data-limit'),
                    column = jQuery(this).attr('data-column'),
                    row = jQuery(this).attr('data-row'),
                    cid = jQuery(this).attr('data-cid'),
                    template = jQuery(this).attr('data-template'),
                    carousel = jQuery(this).attr('data-carousel')
                jQuery.ajax({
                    type: "POST",
                    url: widgetConfig.config.requestUrl,
                    data: {
                        type: type,
                        value: value,
                        limit: limit,
                        carousel: carousel,
                        column: column,
                        cid: cid,
                        row: row,
                        template: template
                    },
                    success: function(data){
                        tab_content.has_content = true;
                        tab_content.append(data);
                        widgetConfig.initCarousel();
                        widgetConfig.initCountdown();
                        widgetConfig.initLazyLoad();
                        jQuery('.widget-spinner').hide();
                        tab_content.css({
                            height: 'auto'
                        });
                    }
                })
            });
        });
    },
    hasTab: function(tab, content){
        if (!tab || !content) return;
        widgetConfig.id.find('.widget-tabs .active').removeClass('active');
        jQuery(tab).parent().addClass('active');
        if (!content.has_content) {
            var prev = widgetConfig.id.find('.tab-pane.active');
            if (prev) {
                content.css('height', prev.height());
                prev.removeClass('active');
                var spinner = jQuery('<div/>').addClass('widget-spinner');
                spinner.css({width: '100%', height: '100%'});
                var spinnerin = jQuery('<div/>').addClass('spinner');
                for (i = 1; i <= 3; i++) {
                    spinnerin.append(jQuery('<span/>').attr('id','bounce'+i));
                }
                spinnerin.css({
                    position: 'absolute',
                    top: '50%',
                    left: '50%'
                });
                spinner.append(spinnerin);
                spinner.css({position: 'relative'});
                content.append(spinner);
            }
        }else {
            widgetConfig.id.find('.tab-pane.active').removeClass('active');
        }
        content.addClass('active');
    },
    initLazyLoad: function(){
        var pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
        if (pixelRatio > 1) {
            jQuery('img[data-srcX2]').each(function () {
                jQuery(this).attr('src', jQuery(this).attr('data-srcX2'));
            });
        }else{
            jQuery('img[data-src]').each(function () {
                jQuery(this).attr('src', jQuery(this).attr('data-src'));
            });
        }
    }
}