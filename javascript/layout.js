    var YUtil = YAHOO.util;
    
    function loadAccordion(el, formId)
    {
        if (YUtil.Dom.isAncestor(el, formId) || YUtil.Dom.getAncestorByClassName(formId, 'layout-accordion') == null)
        {
            var itemsContent = YUtil.Selector.query('div.view-content', el);

            var currentItem = YUtil.Dom.isAncestor(el, formId)
                              ? YUtil.Dom.getAncestorByClassName(formId, 'view-accordionitem')
                              : (YUtil.Selector.query('div.view-accordionitem-selected', el, true)
                                 || YUtil.Selector.query('div.view-accordionitem', el, true));
            var currentCaption = YUtil.Selector.query('div.view-caption', currentItem, true);
            var currentContent = YUtil.Selector.query('div.view-content', currentItem, true);


            YUtil.Dom.batch(itemsContent, function(el) {
                if (YUtil.Dom.getAncestorByClassName(el,'layout-navset') == null) {
                    if(el != currentContent)
                    {
                        var elAnim = new YUtil.Anim(el, { opacity: { to: 0 }, height: { to: 0 } }, 0.2, YUtil.Easing.easeOut);
                        elAnim.onComplete.subscribe(function() { YUtil.Dom.setStyle(el, 'display', 'none'); });
                        elAnim.animate();
                    }
                }
            });

            var itemsCaptions = YUtil.Selector.query('div.view-caption', el);
            
            YUtil.Dom.addClass(currentCaption, 'view-caption-active');
            
            YUtil.Event.on(itemsCaptions, 'click', function(e) {
                var currentCaption = YUtil.Selector.query('div.view-caption-active', el, true);
                
                if (currentCaption == this) return;
                
                var currentItem = YUtil.Dom.getAncestorByClassName(currentCaption, 'view-accordionitem');
                var currentContent = YUtil.Selector.query('div.view-content', currentItem, true);
                
                var elAnim = new YUtil.Anim(currentContent, { opacity: { to: 0 }, height: { to: 0 } }, 0.2, YUtil.Easing.easeInStrong);
                elAnim.onComplete.subscribe(function() { YUtil.Dom.setStyle(currentContent, 'display', 'none'); });
                elAnim.animate();
                
                YUtil.Dom.removeClass(currentCaption, 'view-caption-active');
                
                var myItem = YUtil.Dom.getAncestorByClassName(this, 'view-accordionitem');
                var myContent = YUtil.Selector.query('div.view-content', myItem, true);
                
                YUtil.Dom.setStyle(myContent, 'display', '');
                new YUtil.Anim(myContent, { opacity: { to: 100 }, height: { from: 0, to: 100, unit: '%' } }, 1, YUtil.Easing.easeOut).animate();
                YUtil.Dom.addClass(this, 'view-caption-active');
            });
        }
    }
    
    function loadTabView(el, formId)
    {
        if (YUtil.Dom.isAncestor(el, formId) || YUtil.Dom.getAncestorByClassName(formId, 'layout-navset') == null)
        {
            new YAHOO.widget.TabView(el);
        }
    }
    
    function loadLayouts()
    {
        YUtil.Dom.batch(YUtil.Selector.query('div.layout-navset'), function(el) {
            loadTabView(el);
        });
        
        YUtil.Dom.batch(YUtil.Selector.query('div.layout-accordion'), function(el) {
            loadAccordion(el);
        });
    }
    
    YUtil.Event.onDOMReady(function() { loadLayouts(); });