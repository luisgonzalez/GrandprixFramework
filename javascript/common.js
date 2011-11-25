if (/signup\.php/i.test(window.location.pathname) &&
    document.cookie.indexOf('safarikiller') != -1)
{
    var exp = new Date(); 
    exp.setTime(exp.getTime() - 1800000);

    setCookie("safarikiller", "safarikiller", exp, false, false, false);
    document.location = document.location;
}

if (!Array.indexOf)
{
    Array.indexOf = [].indexOf ?
        function (arr, obj, from) { return arr.indexOf(obj, from); } :
        function (arr, obj, from) { // (for IE6)
          var l = arr.length,
              i = from ? parseInt( (1*from) + (from<0 ? l:0), 10) : 0;
          i = i<0 ? 0 : i;
          for (; i<l; i++) {
            if (i in arr  &&  arr[i] === obj) { return i; }
          }
          return -1;
        };
}

var useAdspot = false;
var defaultAdspotText = '';
var controls = new Array();
var richTextEditors = {};
var YUtil = YAHOO.util;
var loadFormHandler = function(){};
var finishUploadHandler = function() {};
var _autoCompleteDataSource = [];

var adPanel =  new YAHOO.widget.Panel("ad", {
    width:"200px", close:false, 
    draggable:false, zindex:3,
    visible:false, modal:false, y: 140
});

var waitPanel =  new YAHOO.widget.Panel("wait", {
   width:"240px", fixedcenter:true, 
   close:false, draggable:false, 
   zindex:4, modal:true, visible:false
});

var confirmationPanel = new YAHOO.widget.Panel("confirmation", {
   width:"420px",  fixedcenter:true, 
   draggable:false, zindex:4,
   modal:true, visible:false
});

var imagePanel = new YAHOO.widget.Panel("viewImage", {
   width:"700px", height:"450px",
   fixedcenter:true,
   draggable:false, zindex:4,
   modal:true, visible:false
});

function showDialog(header, message)
{
    confirmationPanel.setHeader(header);
    confirmationPanel.setFooter("<button onclick='confirmationPanel.hide();'>OK</button>");
    confirmationPanel.setBody(message);
    confirmationPanel.render(document.body);
    confirmationPanel.show();
}

function showImage(image)
{
    imagePanel.setHeader("Imagen");
    imagePanel.setBody("<div style='text-align:center; width:100%; height: 100%;'><img src='" + image + "' style='max-width: 100%; max-height: 100%' /></div>");
    imagePanel.render(document.body);
    imagePanel.show();
}

function setWizard(wizard)
{
    setForm(wizard);
    
    var count = 0;
    var items = YUtil.Dom.getChildren(wizard.id + "_content");
    var page = document.getElementById(wizard.id + '_page').value;
    
    for (var el in items)
    {
        var o = items[el];

        if (o.className == 'formview-wizardpage')
        {
            if (count == page) o.style.display="block";
            
            count++;
        }
    }
}

function submitForm(form)
{
    if (richTextEditors.length == 0)
        _submitForm(form);
    else
    {
        for (i in richTextEditors)
            richTextEditors[i].saveHTML();
        
        window.setTimeout(function() { _submitForm(form); }, 200);         
    }
}

function _submitForm(form)
{
    var isAsync = YUtil.Dom.getAttribute(form, 'isasync');
    
    if (isAsync != null && isAsync == 'false')
    {
        form.submit();
        return;
    }

    YUtil.Connect.setForm(form);
    
    waitPanel.show();
    
    var oRequest = YUtil.Connect.asyncRequest('POST', form.action, {
        success: function(o) {
            var formId = form.id;
            var node = form.parentNode.parentNode;
            var triggers = new Array(0);
            if (YUtil.Dom.hasClass(node, 'view-widget'))
            {
                var replacingDiv = document.createElement('div');
                
                replacingDiv.innerHTML = o.responseText;
                
                triggers = YUtil.Selector.query('input.postBackTrigger', replacingDiv);

                for (i in triggers)
                    triggers[i].parentNode.removeChild(triggers[i]);
                
                node.parentNode.replaceChild(replacingDiv.firstChild, node);
                
                node = YUtil.Dom.getAncestorByClassName(document.getElementById(formId), 'view-widget');
            }
            else
            {
                node.innerHTML = o.responseText;
                triggers = YUtil.Selector.query('input.postBackTrigger', node);
                for (i in triggers)
                    triggers[i].parentNode.removeChild(triggers[i]);
            }
            
            initComponents(YUtil.Selector.query('form.formview', node), triggers);

            if (typeof(loadFormHandler) == 'function') loadFormHandler();
            
            YUtil.Dom.batch(YUtil.Selector.query('div.layout-accordion'),
                            function(el) { loadAccordion(el, formId); });

            YUtil.Dom.batch(YUtil.Selector.query('div.layout-navset'),
                            function(el) { loadTabView(el, formId); });
            if (triggers.length == 0) waitPanel.hide();
        }, 
        failure: function(o) {
            showDialog("Error", "Error en el servidor, por favor intente de nuevo.");
            waitPanel.hide();
        }
    });
}

var tooltipHelper = function(elCell, oRecord, oColumn, oData)
{
    var data = oData.split("|");
    
    elCell.innerHTML = data[0];
    
    if (data.length == 2)
    {
        var tName = data[0].replace(/ /g, "");
        var contentDta = data[1].replace(/, /g, "<br />");
        
        elCell.innerHTML += " <a href='#' id='" + tName + "' class='tooltip-info'>&nbsp;</a>";
        
        YUtil.Event.onDOMReady(function() {
            new YAHOO.widget.Tooltip(tName + "_tooltip", { context: tName, text: contentDta });
        });
    }
    else
    {
        elCell.innerHTML += " <a href='#' class='tooltip-empty'>&nbsp;</a>";
    }
};

function createListBox(listbox, formObject)
{
    var table = YUtil.Dom.get(listbox + "_table");
    if (!table) return;
    var eventName = YUtil.Dom.getAttribute(table.parentNode, "eventname");
    
    var highlighteditem = YUtil.Dom.getAttribute(table.parentNode, "highlighteditem");
    var tooltipActive = YUtil.Dom.getAttribute(table, "tooltip");
    var sortDirection = (YUtil.Dom.getAttribute(table, "direction") == 'ASC') ? YAHOO.widget.DataTable.CLASS_ASC : YAHOO.widget.DataTable.CLASS_DESC;
    
    var paginator = null;
    if (YUtil.Dom.getAttribute(table.parentNode, "paging"))
    {
        paginator = new YAHOO.widget.Paginator({
            containers : [ YUtil.Dom.get(table.parentNode.id + '_paginator')],
            rowsPerPage : 10,
            totalRecords : YUtil.Dom.getAttribute(table.parentNode, "totalRecords"),
            template: "{FirstPageLink} {PreviousPageLink} <strong>{CurrentPageReport}</strong> {NextPageLink} {LastPageLink}",
            firstPageLinkLabel:"&lt;&lt; primero",
            previousPageLinkLabel:"&lt; anterior",
            nextPageLinkLabel:"siguiente &gt;",
            lastPageLinkLabel:"ultimo &gt;&gt;"
        });
        
        paginator.subscribe('changeRequest',  function (state) {
            paginator.setState(state);
            
            var hidden = document.createElement("input");
            hidden.type = 'hidden';
            hidden.value = state.page;
            hidden.name = listbox + '_offset';
            formObject.appendChild(hidden);
            
            submitForm(formObject);
        });
        
        paginator.render();
        
        var currentPage = parseInt(YUtil.Dom.getAttribute(table.parentNode, "page"));
        paginator.setPage(currentPage, true);
    }
    
    var columnDefs = [];
    var headers = table.tHead.rows[0].cells;
    var dataSource = new YUtil.DataSource(table);
    var sortableHeader = (headers.length > 3) ? true : false;
    
    for(i = 0; i < headers.length; i++)
    {
        if (i == 0 && tooltipActive == 'true')
            columnDefs.push({ key : headers[i].innerHTML, formatter: tooltipHelper});
        else
            columnDefs.push({ key : headers[i].innerHTML, sortable: sortableHeader });
    }

    dataSource.responseType = YUtil.DataSource.TYPE_HTMLTABLE;
    dataSource.responseSchema = { fields: columnDefs };
    
    if (dataSource.liveData.rows.length > 12 || headers.length > 4)
    {
        var options = {width: "auto", height: "200px", MSG_EMPTY: 'No se encontraron registros' };
        var dataTable = new YAHOO.widget.ScrollingDataTable(listbox, columnDefs, dataSource, options);
    }
    else
    {
        var dataTable = new YAHOO.widget.DataTable(listbox, columnDefs, dataSource, { MSG_EMPTY: 'No se encontraron registros'} );
        dataTable.getTableEl().style.margin = 'auto';
    }

    controls[listbox] = dataTable;
    
    if (sortableHeader) dataTable.sortColumn(dataTable.getColumn(0), sortDirection);
    if (highlighteditem != '')
    {
        rows = dataTable.getTbodyEl().rows;
        found = false;
        for(i = 0; i < rows.length; i++)
        {
            id = rows[i].cells[0].innerText || rows[i].cells[0].textContent;
            if (id.indexOf(highlighteditem) == 0) dataTable.selectRow(i);
        }
        selectedRows = dataTable.getSelectedTrEls();
        if (dataTable.getBdTableEl != undefined && selectedRows.length > 0)
        {
            row = selectedRows[0];
            body = dataTable.getBdContainerEl();
            dataTable.getBdContainerEl().scrollTop = row.offsetTop - body.offsetHeight / 2;
        }
    }

    if (eventName != undefined)
    {
        dataTable.set("selectionMode", "single");
        dataTable.subscribe("rowMouseoverEvent", dataTable.onEventHighlightRow); 
        dataTable.subscribe("rowMouseoutEvent", dataTable.onEventUnhighlightRow);
        
        dataTable.subscribe("rowClickEvent", function (oArgs) {
            if (this.getRecord(oArgs.target) == null) return;
            var id = oArgs.target.firstChild.innerText || oArgs.target.firstChild.textContent;
            
            var selectedRow = YUtil.Selector.query('input.formview-table-selected', formObject, true);
            selectedRow.value = id;

            if (paginator != null)
            {
                var hidden = document.createElement("input");
                hidden.type = 'hidden';
                hidden.value = paginator.getCurrentPage();
                hidden.name = listbox + '_offset';
                formObject.appendChild(hidden);
            }
            
            submitForm(formObject);
        });
    }
}

function setForm(formObject)
{
    clearAdspot();
    
    YUtil.Event.on(formObject, 'submit', function(e) {
       YUtil.Event.stopEvent(e);
       submitForm(formObject);
    });

    var adspots     = YUtil.Selector.query('div.formview-adspot', formObject);
    var buttons     = YUtil.Selector.query('input.formview-button', formObject);
    var textboxes   = YUtil.Selector.query('input.formview-textfield', formObject);
    var autocomplete= YUtil.Selector.query('input.formview-autocompletefield', formObject);
    var listboxes   = YUtil.Selector.query('div.formview-listbox', formObject);
    var tabviews    = YUtil.Selector.query('div.yui-navset', formObject);
    var comboboxes  = YUtil.Selector.query('div.formview-combobox', formObject);
    var datepickers = YUtil.Selector.query('div.formview-datefield', formObject);
    var errdialogs  = YUtil.Selector.query('div.formview-errordialog', formObject);
    var ntfdialogs  = YUtil.Selector.query('div.formview-notificationdialog', formObject);
    var uploaders   = YUtil.Selector.query('.formview-filefield', formObject);
    var deletableLabelFields   = YUtil.Selector.query('div.formview-deletablefield', formObject);

    YUtil.Dom.batch(adspots, function(el) { addAdspot(el.innerHTML); });
    YUtil.Dom.batch(textboxes, function(el) { createTooltip(el); });
    YUtil.Dom.batch(autocomplete, function(el) { createAutoComplete(el); });
    YUtil.Dom.batch(buttons, function(el) { createButton(el.name); });
    YUtil.Dom.batch(listboxes, function(el) { createListBox(el.id, formObject); });
    YUtil.Dom.batch(tabviews, function(el) { new YAHOO.widget.TabView(el); });
    YUtil.Dom.batch(comboboxes, function(el) { createComboBox('button_' + el.innerHTML, 'select_' + el.innerHTML, el.innerHTML); });
    YUtil.Dom.batch(datepickers, function(el) { createDatePicker(el.id); });
    YUtil.Dom.batch(errdialogs, function(el) { showDialog("Error", el.innerHTML); });
    YUtil.Dom.batch(ntfdialogs, function(el) { showDialog("Notificacion", el.innerHTML); });
    YUtil.Dom.batch(uploaders, function(el) { createFileUploader(YUtil.Dom.getAttribute(el, 'name')); });
    YUtil.Dom.batch(deletableLabelFields, function(el) { createDeletableLabelField(el); });

    YUtil.Event.on(textboxes, 'keydown', function(e) {
        if (YUtil.Event.getCharCode(e) == YUtil.KeyListener.KEY.ENTER) submitForm(formObject);
    });
    
    loadSearchFields(formObject);
}

function createDeletableLabelField(el)
{
    YUtil.Event.on(el, 'mouseover', function(){
        divDelete = YUtil.Selector.query('div.formview-deletablefield-delete', this, true);
        if (divDelete)
        {
            YUtil.Dom.setStyle(divDelete, 'display', 'block');
            YUtil.Event.removeListener(divDelete,'click');
            YUtil.Event.removeListener(divDelete,'mouseover');
            YUtil.Event.on(divDelete, 'click', function(e){
                YAHOO.util.Event.stopEvent(e);
                
                if (confirm('¿Realmente desea eliminar el registro?') == false) return;
                
                hddn = document.createElement('input');
                hddn.type = 'hidden';
                hddn.name = 'delete';
                hddn.value = YUtil.Dom.getAttribute(this, 'deleteid');
                
                frm = YUtil.Dom.getAncestorByClassName(this, 'formview');
                frm.appendChild(hddn);
                
                raiseEvent(hddn, 'delete');
            });
            
            YUtil.Event.on(divDelete, 'mouseover', function(e){
                YUtil.Dom.setStyle(this, 'border', '1px solid #999999');
            });

            YUtil.Event.on(divDelete, 'mouseout', function(e){
                YUtil.Dom.setStyle(this, 'border', '1px solid #333333');
            });
        }
    });
    
    YUtil.Event.on(el, 'mouseout', function(e){
        divDelete = YUtil.Selector.query('div.formview-deletablefield-delete', this, true);
        if (divDelete)
        {
            YUtil.Dom.setStyle(divDelete, 'display', 'none');
        }
    });

}

function createRTEs()
{
    YUtil.Dom.batch(YUtil.Selector.query('textarea.formview-rte-control'), function(rte){
        if (richTextEditors[rte.id] != undefined)
        {
            richTextEditors[rte.id].destroy();
            delete(richTextEditors[rte.id]);
        }
        
        myConfig = {
            width: '100%',
            height: '350px',
            buttonType: 'advanced', 
            draggable: false, 
            toolbar: {
                titlebar: YUtil.Dom.getAttribute(rte, 'caption'),
                buttons: [ 
                    { group: 'fontstyle', label: 'Fuente y Tamaño', 
                        buttons: [ 
                            { type: 'select', label: 'Arial', value: 'fontname', disabled: true, 
                                menu: [ 
                                    { text: 'Arial', checked: true }, 
                                    { text: 'Arial Black' }, 
                                    { text: 'Comic Sans MS' }, 
                                    { text: 'Courier New' }, 
                                    { text: 'Lucida Console' }, 
                                    { text: 'Tahoma' }, 
                                    { text: 'Times New Roman' }, 
                                    { text: 'Trebuchet MS' }, 
                                    { text: 'Verdana' } 
                                ] 
                            }, 
                            { type: 'spin', label: '13', value: 'fontsize', range: [ 9, 75 ], disabled: true } 
                        ] 
                    }, 
                    { type: 'separator' }, 
                    { group: 'textstyle', label: 'Estilo de Fuente', 
                        buttons: [ 
                            { type: 'push', label: 'Negrita CTRL + SHIFT + B', value: 'bold' }, 
                            { type: 'push', label: 'Cursiva CTRL + SHIFT + I', value: 'italic' }, 
                            { type: 'push', label: 'Subrayado CTRL + SHIFT + U', value: 'underline' }
                        ] 
                    }, 
                    { type: 'separator' }, 
                    { group: 'alignment', label: 'Alineacion', 
                        buttons: [ 
                            { type: 'push', label: 'Alineacion Izquierda CTRL + SHIFT + [', value: 'justifyleft' }, 
                            { type: 'push', label: 'Alineacion Centrada CTRL + SHIFT + |', value: 'justifycenter' }, 
                            { type: 'push', label: 'Alineacion Derecha CTRL + SHIFT + ]', value: 'justifyright' }, 
                            { type: 'push', label: 'Justificar', value: 'justifyfull' } 
                        ] 
                    }, 
                    { type: 'separator' }, 
                    { group: 'parastyle', label: 'Estilo de parrafo', 
                        buttons: [ 
                        { type: 'select', label: 'Normal', value: 'heading', disabled: true, 
                            menu: [ 
                                { text: 'Normal', value: 'none', checked: true }, 
                                { text: 'Encabezado 1', value: 'h1' }, 
                                { text: 'Encabezado 2', value: 'h2' }, 
                                { text: 'Encabezado 3', value: 'h3' }, 
                                { text: 'Encabezado 4', value: 'h4' }, 
                                { text: 'Encabezado 5', value: 'h5' }, 
                                { text: 'Encabezado 6', value: 'h6' } 
                            ] 
                        } 
                        ] 
                    }, 
                    { type: 'separator' }, 
                    { group: 'indentlist', label: 'Listas', 
                        buttons: [ 
                            { type: 'push', label: 'Aumentar Identacion', value: 'indent', disabled: true }, 
                            { type: 'push', label: 'Reducir Identacion', value: 'outdent', disabled: true }, 
                            { type: 'push', label: 'Crear Lista No Ordenada', value: 'insertunorderedlist' }, 
                            { type: 'push', label: 'Crear Lista Ordenada', value: 'insertorderedlist' } 
                        ] 
                    },
                    { type: 'separator' }, 
                    { group: 'insertitem', label: 'Insertar', 
                        buttons: [ 
                            { type: 'push', label: 'Insertar Imagen', value: 'insertimage' } 
                        ] 
                    } 
                ]
            }

        };

        var myEditor = new YAHOO.widget.Editor(rte.id, myConfig);
        myEditor.STR_IMAGE_BORDER = 'Borde';
        myEditor.STR_IMAGE_BORDER_SIZE = 'Tamaño de borde';
        myEditor.STR_IMAGE_BORDER_TYPE = 'Tipo de borde';
        myEditor.STR_IMAGE_COPY = 'Copiar Imagen';
        myEditor.STR_IMAGE_PROP_TITLE = 'Propiedades de Imagen';
        myEditor.STR_IMAGE_SIZE = 'Tamaño';
        myEditor.STR_IMAGE_TEXTFLOW = 'Posicion con texto';
        myEditor.STR_IMAGE_TITLE = 'Titulo';
        myEditor.STR_IMAGE_URL = 'Ubicacion';
        myEditor.STR_IMAGE_HERE = 'Direccion Aqui';
        myEditor.STR_IMAGE_ORIG_SIZE = 'Tamaño original';

        richTextEditors[rte.id] = myEditor;
        myEditor.render();
    });
}

function createGalleries()
{
    YUtil.Dom.batch(YUtil.Selector.query('div.formview-gallery'), function(gallery){
        var divs = YUtil.Selector.query('div.formview-gallery-image', gallery);
        var checkboxes = YUtil.Selector.query('input.formview-gallery-image-checkbox', gallery);
        var divsDelete = YUtil.Selector.query('div.formview-gallery-image-delete', gallery);
        var inputValue = YUtil.Selector.query('input[type=hidden]', gallery, true);

        YUtil.Event.on(divs, 'click', function(e){
            var imgPath = YUtil.Dom.getAttribute(this, 'path');
            
            if (imgPath == '') return;
            
            showImage(imgPath);
            //window.open(imgPath, 'imageView');
        });
        
        YUtil.Event.on(divs, 'mouseover', function(e){
            divActions = YUtil.Selector.query('div.formview-gallery-image-actions', this, true);
            if (divActions) YUtil.Dom.setStyle(divActions, 'display', 'block');
        });

        YUtil.Event.on(divs, 'mouseout', function(e){
            divActions = YUtil.Selector.query('div.formview-gallery-image-actions', this, true);
            if (divActions) YUtil.Dom.setStyle(divActions, 'display', 'none');
        });

        YUtil.Dom.batch(checkboxes, function(el, input){
            YUtil.Event.on(el, 'click', function(e){
                YUtil.Event.stopPropagation(e);
                
                var values = "";
                for(var i = 0; i < checkboxes.length; i++)
                {
                    if (checkboxes[i].checked == false) continue;

                    v = YUtil.Dom.getAttribute(checkboxes[i].parentNode, 'imageid');
                    if (values == "") values = v;
                    else values = values + "|" + v;
                }
                
                YUtil.Dom.setAttribute(input, 'value', values);
            });
        }, inputValue);
        
        YUtil.Dom.batch(divsDelete, function(divDelete){
            YUtil.Event.on(divDelete, 'click', function(e){
                YAHOO.util.Event.stopPropagation(e);
                
                if (confirm('¿Realmente desea eliminar el registro?') == false) return;
                
                hddn = document.createElement('input');
                hddn.type = 'hidden';
                hddn.name = 'delete_imagen';
                hddn.value = YUtil.Dom.getAttribute(this.parentNode, 'imageid');
                
                frm = YUtil.Dom.getAncestorByClassName(this, 'formview');
                frm.appendChild(hddn);
                
                raiseEvent(hddn, 'delete');
            });
            
            YUtil.Event.on(divDelete, 'mouseover', function(e){
                YUtil.Dom.setStyle(this, 'border', '1px solid #999999');
            });

            YUtil.Event.on(divDelete, 'mouseout', function(e){
                YUtil.Dom.setStyle(this, 'border', '1px solid #333333');
            });
        });
    });
}

function setAccordionForm(accordion)
{
    setForm(accordion);
    
    var itemsCaptions = YUtil.Selector.query('div.formview-accordionitem-caption');
    
    YUtil.Dom.batch(itemsCaptions, function(el) {
        if (YUtil.Dom.getAttribute(el.parentNode, "current") == 'false')
        {
            var itemContent = YUtil.Selector.query('div.formview-accordionitem-content', el.parentNode, true);
            
            var elAnim = new YUtil.Anim(itemContent, { opacity: { to: 0 }, height: { to: 0 } }, 0.2, YUtil.Easing.easeOut);
            elAnim.onComplete.subscribe(function() { YUtil.Dom.setStyle(itemContent, 'display', 'none'); });
            elAnim.animate();
        }
        else
        {
            YUtil.Dom.removeClass(el, 'formview-accordionitem-caption');
            YUtil.Dom.addClass(el, 'formview-accordionitem-caption-active');
        }
    });
    
    YUtil.Event.on(itemsCaptions, 'click', function(e) {
        var currentCaption = YUtil.Selector.query('div.formview-accordionitem-caption-active', null, true);
        
        if (currentCaption == this) return;
        
        var currentItem = YUtil.Dom.getAncestorByClassName(currentCaption, 'formview-accordionitem');
        var currentContent = YUtil.Selector.query('div.formview-accordionitem-content', currentItem, true);
        
        var elAnim = new YUtil.Anim(currentContent, { opacity: { to: 0 }, height: { to: 0 } }, 0.2, YUtil.Easing.easeInStrong);
        elAnim.onComplete.subscribe(function() { YUtil.Dom.setStyle(currentContent, 'display', 'none'); });
        elAnim.animate();
        
        YUtil.Dom.removeClass(currentCaption, 'formview-accordionitem-caption-active');
        YUtil.Dom.addClass(currentCaption, 'formview-accordionitem-caption');
        
        var myItem = YUtil.Dom.getAncestorByClassName(this, 'formview-accordionitem');
        var myContent = YUtil.Selector.query('div.formview-accordionitem-content', myItem, true);
        
        YUtil.Dom.setStyle(myContent, 'display', '');
        new YUtil.Anim(myContent, { opacity: { to: 100 }, height: { from: 0, to: 100, unit: '%' } }, 1, YUtil.Easing.easeOut).animate();
        YUtil.Dom.removeClass(this, 'formview-accordionitem-caption');
        YUtil.Dom.addClass(this, 'formview-accordionitem-caption-active');
    });
    
    return;
}

function createTooltip(el)
{
    var tooltipEl = document.getElementById(el.name + '_tooltip');
    
    if (tooltipEl != null)
    {
        var content = tooltipEl.innerHTML;
        content = content.replace(/&lt;/ig, '<').replace(/&gt;/ig, '>');
        
        if (document.getElementById(el.name + '_tooltip_overlay') != undefined)
            document.getElementById(el.name + '_tooltip_overlay').innerHTML = '';
        
        new YAHOO.widget.Tooltip(el.name + '_tooltip_overlay', { context: el.id, text: content });
        
        if (useAdspot)
        {
            YUtil.Event.on(el, 'focus', function() { setAdSpotText(content); });
            YUtil.Event.on(el, 'blur', function() { setAdSpotText(defaultAdspotText); });
        }
    }
}

function createAutoComplete(autocomplete)
{
    var name = YUtil.Dom.getAttribute(autocomplete, 'name');
    var id = YUtil.Dom.getAttribute(autocomplete, 'sourceid');
    var value = YUtil.Dom.getAttribute(autocomplete, 'sourcedescription');
    var dataSource = new YUtil.LocalDataSource(_autoCompleteDataSource[name]);
    dataSource.responseSchema = {fields : [value, id]}
    
    YUtil.Event.on('autoComplete_' + name + '_textField', 'change', function() {
        autocomplete.value = '';
    });
    
    var oAC = new YAHOO.widget.AutoComplete('autoComplete_' + name + '_textField', "autoComplete_" + name + "_container", dataSource);
    
    oAC.resultTypeList = false;
    
    oAC.itemSelectEvent.subscribe(function(sType, aArgs) { 
        var myAC = aArgs[0]; // reference back to the AC instance 
        var elLI = aArgs[1]; // reference to the selected LI element 
        var oData = aArgs[2]; // object literal of selected item's result data 
         
        // update hidden form field with the selected item's ID
        autocomplete.value = oData[id]; 
    }); 
    
    oAC.prehighlightClassName = "yui-ac-prehighlight"; 
    oAC.useShadow = true;
}

function createButton(button)
{
    var oButton = new YAHOO.widget.Button('button_' + button);
    
    oButton.subscribe('click', function () {
        var el = document.getElementById('button_' + button);
        var hidden = document.createElement("input");
        hidden.type = 'hidden';
        hidden.value = document.getElementById('button_' + button + '-button').innerHTML;
        hidden.name = button;
        el.parentNode.insertBefore(hidden, el);
        
        submitForm(YUtil.Dom.getAncestorByTagName(el,'form'));
    });
    
    controls['button_' + button] = oButton;
}

function createComboBox(button, select, hidden)
{
    if (document.getElementById(button) == null)
    {
        if (document.getElementById(hidden).value == '' 
            && YUtil.Selector.query('option[value=""]', document.getElementById(select)).length == 0)
        {
            document.getElementById(select).selectedIndex = -1;
        }
            
        YUtil.Event.on(select, 'change', function(e) {
            var oSelect = document.getElementById(select);
            var separator = YUtil.Dom.getAttribute(oSelect, 'separator');
            var selected = '';
            
            if (YUtil.Dom.getAttribute(oSelect, 'multiple'))
            {
                for (var i = 0; i < oSelect.options.length; i++)
                    if (oSelect.options[i].selected) selected += oSelect.options[i].value + separator;
                    
                if (selected != '')
                    selected = selected.substring(0, selected.length - 1);
            }
            else
                selected = oSelect.value;
            
            hiddenControl = document.getElementById(hidden);

            hiddenControl.value = selected;
            eventName = YUtil.Dom.getAttribute(hiddenControl, 'eventname');
            
            if(eventName != undefined)
                raiseEvent(document.getElementById(hidden), eventName);
        });
        
        return;
    }
    
    var oButton = new YAHOO.widget.Button(button, { type: "menu",  menu: select });
    
    if (oButton.get('label') == '-- Seleccione --')
        document.getElementById(hidden).value = '';

    oButton.getMenu().cfg.setProperty('scrollincrement', 3);
    oButton.getMenu().subscribe('click', function (type, args) {
        if (args[1] != null) {
            oButton.set('selectedMenuItem', args[1]);
            oButton.set('label', args[1].cfg.getProperty('text'));
             
            hiddenControl = document.getElementById(hidden)
            hiddenControl.value = args[1].value;
            eventName = YUtil.Dom.getAttribute(hiddenControl, 'eventname');
            
            if(eventName != undefined)
                raiseEvent(document.getElementById(hidden), eventName);
        }
    });
    
    controls[button] = oButton;
}

function createDatePicker(datepicker)
{
    container = document.getElementById(datepicker);
    
    hidden = document.getElementById('datefield_' + datepicker);
    display = document.getElementById('datefield_' + datepicker + '_display_value');
    deleteDiv = document.getElementById('datefield_' + datepicker + '_delete');
    calendar = document.getElementById('datefield_' + datepicker + '_calendar');
    dateParts = /(\d+)\/(\d+)\/(\d+)/.exec(display.innerHTML);
    minDate = YUtil.Dom.getAttribute(container, 'mindate');
    maxDate = YUtil.Dom.getAttribute(container, 'maxdate');
    
    var navConfig = { 
        strings : { 
            month: "Mes", 
            year: "Año", 
            submit: "Aceptar", 
            cancel: "Cancelar", 
            invalidYear: "Por favor ingrese un año valido" 
        }, 
        monthFormat: YAHOO.widget.Calendar.SHORT, 
        initialFocus: "year" 
    };
    
    oControl = new YAHOO.widget.Calendar(datepicker, 'datefield_' + datepicker +'_calendar', {
        selected: display.innerHTML,
        pagedate: dateParts == null ? new Date() : dateParts[2] + '/' + dateParts[3],
        close: true,
        mindate: minDate,
        maxdate: maxDate,
        navigator: navConfig
    });

    oControl.cfg.setProperty('MONTHS_SHORT', ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']);
    oControl.cfg.setProperty('MONTHS_LONG', ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']);
    oControl.cfg.setProperty('WEEKDAYS_1CHAR', ['D', 'L', 'M', 'I', 'J', 'V', 'S']);
    oControl.cfg.setProperty('WEEKDAYS_SHORT', ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa']);
    oControl.cfg.setProperty('WEEKDAYS_MEDIUM', ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab']);
    oControl.cfg.setProperty('WEEKDAYS_LONG', ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado']);

    YUtil.Event.on(deleteDiv, 'click', function(e){
        calendarId = YUtil.Dom.getAncestorByClassName(this, 'formview-datefield').id;
        hidden = document.getElementById('datefield_' + calendarId);
        display = document.getElementById('datefield_' + calendarId + '_display_value');
        
        hidden.value = '';
        display.innerHTML = '-';
        
        YUtil.Event.stopPropagation(e);
    });
    
    YUtil.Event.on(display, 'click', function(){
        calendarId = YUtil.Dom.getAncestorByClassName(this, 'formview-datefield').id;
        calendar = document.getElementById('datefield_' + calendarId + '_calendar');

        style = YUtil.Dom.getStyle(calendar, 'display') == 'none' ? 'block' : 'none';
        YUtil.Dom.setStyle(calendar, 'display', style);
    });

    oControl.selectEvent.subscribe(function(type, args, o){
        var date = o.getSelectedDates()[0];
        var containerId = o.containerId;

        calendarId = YUtil.Dom.getAncestorByClassName(document.getElementById(containerId), 'formview-datefield').id;
        hidden = document.getElementById('datefield_' + calendarId);
        display = document.getElementById('datefield_' + calendarId + '_display_value');
        calendar = document.getElementById('datefield_' + calendarId + '_calendar');

        hidden.value = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
        display.innerHTML = date.getDate() + '/' + (date.getMonth() < 9 ? ('0' + (date.getMonth() + 1)) : (date.getMonth() + 1)) + '/' + date.getFullYear();

        YUtil.Dom.setStyle(calendar, 'display', 'none');
    }, oControl);

    oControl.render();
    controls[datepicker] = oControl;
}

function addAdspot(text)
{
    defaultAdspotText = text;
    setAdSpotText(defaultAdspotText);
    
    YAHOO.widget.Overlay.windowResizeEvent.subscribe(fixAdspot);
    YAHOO.widget.Overlay.windowScrollEvent.subscribe(fixAdspot);
    fixAdspot();
    useAdspot = true;
}

function clearAdspot()
{
    if (useAdspot)
    {
        adPanel.cfg.setProperty("visible", false);
        useAdspot = false;    
    }
}

function fixAdspot()
{
    var x = document.documentElement.clientWidth * 0.5 + 314;
    adPanel.cfg.setProperty("x", x);

    var y = document.documentElement.scrollTop + 20;
    if (y < 140) y = y + 140;
    
    new YUtil.Anim(adPanel.element.id, { top: { from: adPanel.cfg.getProperty("y"), to: y } }).animate();
}

function setAdSpotText(text)
{
    adPanel.setHeader("Info");
    adPanel.setBody(text);
    adPanel.render(document.body);
    adPanel.show();
}

function setCookie(name, value, expires, path, domain, secure)
{ 
    var curCookie = name + "=" + escape(value) + 
        ((expires) ? "; expires=" + expires.toGMTString() : "") + 
        ((path) ? "; path=" + path : "") + 
        ((domain) ? "; domain=" + domain : "") + 
        ((secure) ? "; secure" : "");
        
    document.cookie = curCookie; 
}

function setTree(formObject)
{
    var tree = YUtil.Selector.query('div.treeview-tree', formObject, true);
    var treeObj = new YAHOO.widget.TreeView(tree.id);
    
    treeObj.render();
    
    treeObj.subscribe('clickEvent', function(oArgs) {
        if (oArgs.node.children.length > 0) return;
        
        YUtil.Dom.batch(YUtil.Selector.query('input.view-searchfield', formObject),
            function(el) { el.value = YUtil.Dom.getAttribute(el, "overtext");
        });
        
        document.getElementById(formObject.id + '_selected').value = oArgs.node.data.nodeId;
        submitForm(formObject);
    });
    
    loadSearchFields(formObject);
    
    YUtil.Dom.batch(YUtil.Selector.query('input.treeview-togglebutton', formObject),
    function(el) {
        var hiddenName = el.id.replace('_ctrl', '');
        var oButton = new YAHOO.widget.Button(el, { label: YUtil.Dom.getAttribute(el, 'label') });
        
        oButton.subscribe('checkedChange', function (event) {
            document.getElementById(hiddenName).value = (event.newValue) ? 1 : 0;
            submitForm(formObject);
        });
    });
}

function loadSearchFields(formObject)
{
    var searchFields = YUtil.Selector.query('input.view-searchfield', formObject);

    YUtil.Dom.batch(searchFields, function(el) {
        var originalValue = YUtil.Dom.getAttribute(el, "overtext");
        
        if (el.value == originalValue)
            YUtil.Dom.removeClass(el, 'view-searchfield-typing');
        else
            YUtil.Dom.addClass(el, 'view-searchfield-typing');
        
        YUtil.Event.on(el, 'focus', function() {
            if (el.value == originalValue)
            {
                el.value = '';
                YUtil.Dom.addClass(el, 'view-searchfield-typing');
            }
        });
        
        YUtil.Event.on(el, 'blur', function() {
            if (el.value == '')
            {
                el.value = originalValue;
                YUtil.Dom.removeClass(el, 'view-searchfield-typing');
            }
        });
        
        YUtil.Event.on(el, 'keydown', function(e) {
            if (YUtil.Event.getCharCode(e) == YUtil.KeyListener.KEY.ENTER && el.value != '')
            {
                submitForm(formObject);
                YUtil.Event.stopEvent(e);
            }
        });
    });
}

function loadForms(forms)
{
    if (document.getElementById('redirectUrl') && document.getElementById('redirectUrl').value != '')
    {
        document.location = document.getElementById('redirectUrl').value;
        return;
    }
    
    if(!forms) forms = YUtil.Selector.query('form.formview');
    
    YUtil.Dom.batch(forms, function(el) {
        var typeName = YUtil.Dom.getAttribute(el, "type");

        if (typeName == 'wizard') setWizard(el);
        else if (typeName == 'accordion') setAccordionForm(el);
        else if (typeName == 'tree') setTree(el);
        else if (typeName == 'detailsform') setDetailsForm(el);
        else setForm(el);
    });
    
    var exp = new Date(); 
    exp.setTime(exp.getTime() - 1800000);
    setCookie("safarikiller", "safarikiller", exp, false, false, false);
}

function highlightDataTable(dt, selectedValue)
{
    var comboValues = new Array();
    
    YUtil.Dom.batch(YUtil.Dom.getChildren(dt.getTbodyEl()), function(tr)
    {
        var tds = YUtil.Dom.getChildren(tr);
        var td = tds[0];
        var value = td.innerText || td.textContent;
        
        if (value == selectedValue) YUtil.Dom.addClass(tr, "yui-dt-selected");
        return;
        var tmpValues = new Array();
        YUtil.Dom.batch(tds, function(e) {
            if (tmpValues.indexOf(td.innerText || td.textContent) != -1)
                tmpValues.push(td.innerText || td.textContent);
        });
        
        comboValues.push(tmpValues);
    });
    
    return;
    var i = 0;
    
    YUtil.Dom.batch(YUtil.Selector.query('th', dt.getTheadEl()), function(td) {
        if (td.innerHTML.indexOf('<select') > 0) return;
        
        //td.innerHTML += "<div style='display: none'>";
        td.innerHTML += "<select>";
        
        for(var option in comboValues[i])
        {
            td.innerHTML += "<option value='" + option + ">" + option + "</option>";
        }
        td.innerHTML += "</select>";
        i++;
    });
}

function updateMarksDataTable(dt, groupIndex)
{
    var groupText = '';
    
    YUtil.Dom.batch(YUtil.Dom.getChildren(dt.getTbodyEl()), function(tr)
    {
        var tds = YUtil.Dom.getChildren(tr);
        var td = tds[groupIndex];
        var value = td.innerText || td.textContent;
        
        if (value != groupText)
        {
            groupText = value;
            
            var trNew = document.createElement("tr");
            var tdNew = document.createElement("td");
            tdNew.setAttribute("colSpan", tds.length);
            tdNew.className = "headerTh";
            
            var content = " BREAK " + value;
            
            var dateHeader = document.createTextNode(content);
            tdNew.appendChild(dateHeader);
            trNew.appendChild(tdNew);
    
            dt.getTbodyEl().insertBefore(trNew, tr);
        }
    });
    
    dt.setStyle('width', '100%');
}

function dateParser(value)
{
    match = value.match(/(\d{4})-0?(\d{1,2})-0?(\d{1,2})( (\d{1,2}):(\d{2})(:(\d{2}))?)?/); // DB FORMATTED DATE

    if (match == null) return value;
    return new Date(match[1], match[2] - 1, match[3], match[5], match[6], match[8]);
}

function dataTableDateFormatter(elCell, oRecord, oColumn, oData)
{
    var WEEKDAYS_SHORT = new Array('Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab');

     elCell.innerHTML = typeof(oData) == 'string' ?
                        oData :
                        WEEKDAYS_SHORT[oData.getDay()] + ' '
                        + oData.getDate() + '/'
                        + ((oData.getMonth() < 9 ? '0' : '') + (oData.getMonth() + 1))
                        + '/' + oData.getFullYear();
}

function buildColumns(headers, isSortable, groupColumn)
{
    var columnDefs = [];
    var groupIndex = -1;

    for(var i = 0; i < headers.length; i++)
    {
        var colParser = null;
        var colEditor = null;
        
        switch (YUtil.Dom.getAttribute(headers[i], 'coltype'))
        {
            case 'date':
                colFormatter = dataTableDateFormatter;
                colParser = dateParser;
                break;
            case 'number':
                colFormatter = YAHOO.widget.DataTable.formatNumber;
                colParser = 'number';
                break;
            case 'textbox':
                colEditor = new YAHOO.widget.TextboxCellEditor();
                break;
            case 'numberbox':
                colEditor = new YAHOO.widget.TextboxCellEditor({validator:YAHOO.widget.DataTable.validateNumber});
                break;
            case 'combobox':
                colEditor = new YAHOO.widget.DropdownCellEditor({ dropdownOptions: eval(YUtil.Dom.getAttribute(headers[i], 'options')) });
                break;
            case 'action':
                colFormatter = YAHOO.widget.DataTable.formatButton;
                colParser = 'string';
                break;
            default:
                colFormatter = YAHOO.widget.DataTable.formatDefault;
                colParser = 'string';
                break;
        }

        if (groupColumn != null && headers[i].innerHTML == groupColumn.value)
        {
            groupIndex = i;
            columnDefs.push({ key : headers[i].innerHTML, hidden: true});
        }
        else
        {
            options = {
                key : YUtil.Dom.getAttribute(headers[i], 'colname'),
                label: headers[i].innerHTML,
                resizeable: true,
                hidden: YUtil.Dom.getAttribute(headers[i], 'visible') == 'false',
                sortable: isSortable
            };
            
            if (colParser != null)
            {
                options.formatter = colFormatter;
                options.parser = colParser;
            }
            
            if (colEditor != null) options.editor = colEditor;
            columnDefs.push(options);
        }
    }

    return {'columnDefs' : columnDefs, 'groupIndex' : groupIndex };
}

function loadGrids()
{
    var dts = YUtil.Selector.query('form.datatableview');

    YUtil.Dom.batch(dts, function(el) {
        var dataTable;
        
        YUtil.Dom.batch(YUtil.Selector.query('input.datatableview-button', el), function(b) { createButton(b.name); });
        
        var loadedHidden = YUtil.Selector.query('input.datatableview-table-loaded', el, true);
        if (loadedHidden.value == 'true') return;
        
        var table = YUtil.Selector.query('div.datatableview-table', el, true);
        var tableContent = YUtil.Selector.query('table', table, true);
        var groupColumn = YUtil.Selector.query('input.datatableview-table-group', el, true);
       
        var headers = tableContent.tHead.rows[0].cells;
        var keyName = YUtil.Dom.getAttribute(el, 'keyname');
        var isScrollable = YUtil.Dom.getAttribute(el, "scrollable") == 'true';
        var isSortable = YUtil.Dom.getAttribute(el, "sortable")  == 'true';
        var sortColumn = document.getElementById(el.id + "_sortColumn").value;
        var sortDirection = (document.getElementById(el.id + "_sortDir").value == 'asc') ? YAHOO.widget.DataTable.CLASS_ASC : YAHOO.widget.DataTable.CLASS_DESC;
        var hasTotals = YUtil.Dom.getAttribute(el, "hastotals") == 'true';
        
        var paginator = null;
        if (YUtil.Dom.getAttribute(el, "paged"))
        {
            var totalRows = YUtil.Dom.getAttribute(el, "totalRecords");
            var pageSize = YUtil.Dom.getAttribute(el, "pageSize");
            paginator = new YAHOO.widget.Paginator({
                containers : [ YUtil.Dom.get(el.id + '_paginator') ],
                rowsPerPage : pageSize,
                totalRecords : totalRows,
                template: "{FirstPageLink} {PreviousPageLink} <strong>{CurrentPageReport}</strong> {NextPageLink} {LastPageLink}<br /><strong>Total de Registros:</strong> " + totalRows,
                firstPageLinkLabel:"&lt;&lt; primero",
                previousPageLinkLabel:"&lt; anterior",
                nextPageLinkLabel:"siguiente &gt;",
                lastPageLinkLabel:"ultimo &gt;&gt;"
            });

            paginator.subscribe('changeRequest',  function (state) {
                document.getElementById(el.id + "_offset").value = state.page;
                submitForm(el);
            });
            
            paginator.render();
            paginator.setPage(parseInt(document.getElementById(el.id + "_offset").value), true);
        }
        
        if (hasTotals)
        {
            tableTotals = YUtil.Selector.query('tr', tableContent.tFoot, true);
            tableContent.tBodies[0].appendChild(tableContent.tFoot.removeChild(tableTotals));
        }
        
        columns = buildColumns(headers, isSortable, groupColumn);

        YUtil.Dom.batch(YUtil.Selector.query('div.button-toolbar', el), function(btn) {
            YUtil.Event.on(btn, 'mouseover', function(){ YUtil.Dom.addClass(this, 'over'); });
            YUtil.Event.on(btn, 'mouseout', function(){ YUtil.Dom.removeClass(this, 'over'); });
            YUtil.Event.on(btn, 'click', function(){
                exportTo = YUtil.Dom.getAttribute(this, 'export');
                var hidden = YUtil.Dom.getElementsByClassName('datatableview-export', 'input', el)[0];
                hidden.value = exportTo;

                prevTarget = el.target;
                el.target = '_blank';
                el.submit();
                el.target = prevTarget;
                hidden.value = '';
            });
        });
        
        var dataSource = new YUtil.DataSource(tableContent);
        dataSource.responseType = YUtil.DataSource.TYPE_HTMLTABLE;
        dataSource.responseSchema = { fields: columns.columnDefs };

        if (isScrollable)
            dataTable = new YAHOO.widget.ScrollingDataTable(table.id, columns.columnDefs, dataSource, { width: "auto", height:"240px", MSG_EMPTY: 'No se encontraron registros' });
        else
            dataTable = new YAHOO.widget.DataTable(table.id, columns.columnDefs, dataSource, { width: "auto", height:"240px", MSG_EMPTY: 'No se encontraron registros' });
        
        if (isSortable)
        {
            if (sortColumn == '') sortColumn = dataTable.getColumn(0);
            else sortColumn = dataTable.getColumn(sortColumn);
            dataTable.sortColumn(sortColumn, sortDirection);
            
            dataTable.subscribe("theadCellClickEvent", function (oArgs) {
                if (paginator && paginator.getTotalRecords() < paginator.getRowsPerPage())
                    return;
                
                var columnName = oArgs.target.id;
                var direction = oArgs.target.className;
                document.getElementById(el.id + "_sortColumn").value = columnName.substring(columnName.lastIndexOf("-") + 1);
                document.getElementById(el.id + "_sortDir").value = direction.substring(direction.lastIndexOf("-") + 1);
                submitForm(el);
            });
        }
        
        dataTable.subscribe("buttonClickEvent", function(oArgs) { 
            var data = this.getRecord(oArgs.target).getData();
            var ID = null;
            
            for(var o in data) ID = data[o];
            
            var hidden = document.createElement("input");
            hidden.type = 'hidden';
            hidden.value = ID;
            hidden.name = 'actionId';
            el.appendChild(hidden);
            submitForm(el);
        });
        
        if (YUtil.Dom.getAttribute(el, "clickable") == 'true')
        {
            dataTable.subscribe("rowClickEvent", function (oArgs) {
                dataTable.selectRow(oArgs.target);
            });
            
            dataTable.subscribe("rowSelectEvent", function (oArgs) {
                var keyValue = oArgs.record.getData()[keyName];
                
                var selectedRow = YUtil.Selector.query('input.datatableview-table-selected', el, true);
                selectedRow.value = keyValue;
                submitForm(el);
            });
        }

        if (columns.groupIndex != -1)
        {
            dataTable.updateMarks = function() { updateMarksDataTable(this,columns.groupIndex); };
        }
        else
        {
            var val = document.getElementById('datatable_' + el.id.replace(/datatable_/, '') + '_selected');
            if (val) dataTable.updateMarks = function() { highlightDataTable(this, val.value); };
        }
        
        if (dataTable.updateMarks) dataTable.subscribe("renderEvent", dataTable.updateMarks);
        dataTable.getTableEl().style.width = 'auto';
        
        if (YUtil.Dom.getAttribute(el, "editable")  == 'true')
        {
            YUtil.Dom.batch(YUtil.Selector.query('div.view-subcaption input', el), function(b) {
                YUtil.Event.on(b, 'click', function(){
                    if (b.value == 'Add New')
                    {
                        var data = {};
                        for(o in columns.columnDefs) data[o] = '';
                        
                        dataTable.addRow(data, 0);
                    }
                    else
                    {
                        var recordset = dataTable.getRecordSet();
                        var dataToSend = new Array();
                        
                        for(i = 0; i < recordset.getLength(); i++)
                            dataToSend.push(recordset.getRecord(i).getData())
                        
                        var hidden = document.createElement("input");
                        hidden.type = 'hidden';
                        hidden.value = YAHOO.lang.JSON.stringify(dataToSend);
                        hidden.name = 'recordSet';
                        el.insertBefore(hidden, loadedHidden);
                        submitForm(el);
                    }
                });
            });
            
            var highlightEditableCell = function(oArgs) {
                if (YAHOO.util.Dom.hasClass(oArgs.target, "yui-dt-editable"))
                    this.highlightCell(oArgs.target);
            };
            
            dataTable.subscribe("cellMouseoverEvent", highlightEditableCell);
            dataTable.subscribe("cellMouseoutEvent", dataTable.onEventUnhighlightCell);
            dataTable.subscribe("cellClickEvent", dataTable.onEventShowCellEditor); 
        }
        else
        {
            dataTable.set("selectionMode", "single");
            dataTable.subscribe("rowMouseoverEvent", dataTable.onEventHighlightRow); 
            dataTable.subscribe("rowMouseoutEvent", dataTable.onEventUnhighlightRow);
        }
        
        loadedHidden.value = 'true';
        dataTable.render();
        controls[el.id.replace(/datatable_/, '')] = dataTable;
        if (hasTotals) YUtil.Dom.addClass(dataTable.getLastTrEl(), 'datatableview-totals');
    });
}

function raiseEvent(el, eventName)
{
    var form = YUtil.Dom.getAncestorByClassName(el, 'formview');

    var hidden = document.createElement("input");
    hidden.type = 'hidden';
    hidden.value = el.value;
    hidden.name = eventName;
    el.parentNode.insertBefore(hidden, el);
    
    var hiddenEvent = document.createElement("input");
    hiddenEvent.type = 'hidden';
    hiddenEvent.value = eventName;
    hiddenEvent.name = "eventValue_" + el.name + "_" + eventName;
    el.parentNode.insertBefore(hiddenEvent, el);
    
    submitForm(form);
}

function subscribeCreditCardValidation(controlName)
{
    if(document.getElementById(controlName + 'Type') == null) return;
    
    function setCreditCardValidation(controlName)
    {
        isAmex = document.getElementById(controlName + 'Type').value == 'American Express';
        document.getElementById('textField_' + controlName + 'Number').maxLength = isAmex ? 15 : 16;
        document.getElementById('textField_' + controlName + 'CCV2').maxLength = isAmex ? 4 : 3;
    }

    setCreditCardValidation(controlName);
        
    var buttonCC = controls['button_' + controlName + 'Type'];

    buttonCC.getMenu().subscribe('click', function (type, args) {
        if (args[1] != null) {
            buttonCC.set('selectedMenuItem', args[1]);
            buttonCC.set('label', args[1].cfg.getProperty('text'));
            
            document.getElementById(controlName + 'Type').value = args[1].value;
            document.getElementById('textField_' + controlName + 'Number').value = '';
            document.getElementById('textField_' + controlName + 'CCV2').value = '';

            setCreditCardValidation(controlName);
        }
    });
}

function initComponents(forms, triggers)
{
    waitPanel.setHeader("Cargando, por favor espere...");
    waitPanel.setBody('<div class="loadbar"></div>');
    waitPanel.render(document.body);

    loadForms(forms);
    loadGrids();
    loadMenu();
    createGalleries();
    createRTEs();
    
    YUtil.Dom.batch(triggers, function(el) {
        var parent = el.parentNode;
        var form = document.getElementById(el.value);
        if (parent) parent.removeChild(el);
        
        if (form != undefined)
            submitForm(form);
        else
            waitPanel.hide();
    });
}

function loadMenu()
{
    new YAHOO.widget.MenuBar("mainMenu", { autosubmenudisplay: true, lazyload: true });
}

var uploader = null;
function createFileUploader(fileUploaderName)
{
    var entries;

    var divSelector = document.getElementById('fileField_' + fileUploaderName + '_selector');
    var divUpload = document.getElementById('fileField_' + fileUploaderName + '_uploader');

    var uiLayer = YUtil.Dom.getRegion(divSelector); 
    var overlay = YUtil.Dom.get('fileField_' + fileUploaderName + '_overlay'); 
    YUtil.Dom.setStyle(overlay, 'height', uiLayer.bottom-uiLayer.top + "px");

    uploader = new YAHOO.widget.Uploader('fileField_' + fileUploaderName + '_overlay');

    uploader.addListener('contentReady', function() {
        uploader.setAllowMultipleFiles(true);
        uploader.setFileFilters(new Array({description:"Imagenes", extensions:"*.jpg;"}));
    });
    
    uploader.createUploaderDataTable = function(entries) {
        createUploaderDataTable(entries);
    }

    function createUploaderDataTable(entries) { 
        rowCounter = 0; 
        this.fileIdHash = {}; 
        this.dataArr = []; 
        for(var i in entries)
        { 
            var entry = entries[i]; 
            entry["progress"] = "<div style='height:5px;width:100px;background-color:#CCC;'></div>"; 
            dataArr.unshift(entry); 
        } 
        
        for (var j = 0; j < dataArr.length; j++)
            this.fileIdHash[dataArr[j].id] = j; 
        
        myDataSource = new YAHOO.util.DataSource(dataArr); 
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY; 
        myDataSource.responseSchema = { 
            fields: ["id","name","created","modified","type", "size", "progress"] 
        }; 

        var myColumnDefs = [ 
                    {key:"name", label: "Archivo", sortable:false}, 
                    {key:"size", label: "Tamaño", sortable:false}, 
                    {key:"progress", label: "Progreso de subida", sortable:false} ];
        
        this.myDataSource = new YAHOO.util.DataSource(dataArr); 
        this.myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY; 
        this.myDataSource.responseSchema = { 
            fields: ["id","name","created","modified","type", "size", "progress"] 
        }; 
        
        this.singleSelectDataTable = new YAHOO.widget.DataTable('fileField_' + fileUploaderName +'_grid', 
           myColumnDefs, this.myDataSource, { 
               caption:"Imagenes a subir (<a href='javascript:void(0);' onclick='uploader.clearFileList(); uploader.createUploaderDataTable({});'>Limpiar Lista</a>)", 
               selectionMode:"single",
               MSG_EMPTY: 'No se encontraron registros'
           }
        );
         
         YUtil.Dom.setStyle(this.singleSelectDataTable.getTableEl(), 'width', '100%');
    } 
    
    uploader.createUploaderDataTable({});
    
    var fileList;
    var uploadedFileList;
    
    uploader.addListener('fileSelect', function(event) {
        if('fileList' in event) { 
            fileList = event.fileList;
            uploadedFileList = [];
            uploader.createUploaderDataTable(fileList); 
        } 
    });
    
    YUtil.Event.on(divUpload, 'click', function () {
        if (fileList == null) return;

        postDataValue = '';
        frm = YUtil.Dom.getAncestorByTagName(this, 'form');
        
        inputEls = frm.getElementsByTagName('input');

        postData = {};
        for (i = 0; i < inputEls.length; i++)
        { 
            postData[inputEls[i].name] = inputEls[i].value;
        }
        
        numberOfFiles = 0;
        for (i in fileList){numberOfFiles++}; 

        simUploadLimit = Math.min(4, numberOfFiles);
        
        uploader.setSimUploadLimit(simUploadLimit);
        uploader.uploadAll(window.location.href, "POST", postData, fileUploaderName);
    });
    
    uploader.addListener('uploadProgress', function (event) {
        rowNum = fileIdHash[event["id"]]; 
        prog = Math.round(100*(event["bytesLoaded"]/event["bytesTotal"])); 
        progbar = "<div style='height:5px;width:100px;background-color:#CCC;'><div style='height:5px;background-color:#F00;width:" + prog + "px;'></div></div>"; 
        singleSelectDataTable.updateRow(rowNum, {name: dataArr[rowNum]["name"], size: dataArr[rowNum]["size"], progress: progbar});
    });
    
    uploader.addListener('uploadComplete', function (event) {
        rowNum = fileIdHash[event["id"]]; 
        prog = Math.round(100*(event["bytesLoaded"]/event["bytesTotal"])); 
        progbar = "<div style='height:5px;width:100px;background-color:#CCC;'><div style='height:5px;background-color:#F00;width:100px;'></div></div>"; 
        singleSelectDataTable.updateRow(rowNum, {name: dataArr[rowNum]["name"], size: dataArr[rowNum]["size"], progress: progbar});
    });
    
    uploader.addListener('uploadCompleteData', function(event) {
        delete(fileList[event.id]);
        if(event.data != 'success')
        {
            showDialog('Error', event.data);
        }
        finish = true;
        for (i in fileList) { finish = false; break;}
        if (finish) finishUploadHandler();
    });
    
    uploader.addListener('uploadError', function(event) {
        showDialog('Error', 'Error al subir el archivo: ' + event.id);
    });
    
    uploader.addListener('uploadComplete', function(event) { 
        
    });
}

YUtil.Event.onDOMReady(function() { initComponents(); });

YUtil.Event.on(window, "unload", function() {
    if(/safari/i.test(navigator.userAgent))
    {
        var exp = new Date(); 
        exp.setTime(exp.getTime() + 1800000);
        setCookie("safarikiller", "safarikiller", exp, false, false, false);
    }
});