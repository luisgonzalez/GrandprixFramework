function loadCharts()
{
    lineCharts = YUtil.Selector.query('.linechart .chartview-container');
    barCharts = YUtil.Selector.query('.barchart .chartview-container');
    YUtil.Dom.batch(lineCharts, loadLineChart);
    YUtil.Dom.batch(barCharts, loadBarChart);
}

function loadBarChart(chart)
{
    chartId = chart.id;

    tables = YUtil.Dom.getElementsByClassName('chartview-table','table',chart);
    if (tables.length == 0) return;

    var tbl = tables[0];

    series = YUtil.Dom.getElementsByClassName('chartview-datafield','span',chart);

    var columnDefs = [];
    var seriesDefs = [];
    var baseField = series[0].innerHTML;
    var baseFieldColumn = 0;

    // Column Defs
    var headers = tbl.tHead.rows[0].cells;

    columnDefs = buildColumns(headers, false, false, null).columnDefs;

    // Just loop to get the position (j)
    for(var j = 0; j < columnDefs.length && columnDefs[j].key != series[0].innerHTML; j++) void(0);

    baseFieldColumn = j;

    div = tbl.parentNode;
    divCheckboxes = document.createElement('div');

    seriesDefs = [];

    for (var i = 1; i < series.length; i++)
    {
        // Just loop to get the position (j)
        for(var j = 0; j < columnDefs.length && columnDefs[j].key != series[i].innerHTML; j++) void(0);
        
        seriesDefs.push({
            displayName:columnDefs[j].label.replace(/&nbsp;/g,' '),
            yField:series[i].innerHTML
        });
    }

    maxValue = 0;
    var rows = tbl.tBodies[0].rows;
    
    for (var i = 0; i < rows.length; i++)
    {
        for (var j = 0; j < rows[i].cells.length - 1; j++)
        {
            if (j == baseFieldColumn) continue;

            maxValue = Math.max(maxValue, parseInt(rows[i].cells[j].innerHTML));
        }
    }

    // DataSource
    var dataSource = new YUtil.DataSource(tbl);
    dataSource.responseType = YUtil.DataSource.TYPE_HTMLTABLE;
    dataSource.responseSchema = { fields: columnDefs };

    obj =
    {
        xField: baseField,
        series: seriesDefs,
        height: 300
    };
    
    controls[chartId] = new YAHOO.widget.ColumnChart(div, dataSource, obj);

    var yAxis = new YAHOO.widget.NumericAxis(); 
    // yAxis.maximum = parseInt(maxValue) + 1;
    // yAxis.minimum = 0;
    controls[chartId].set("yAxis", yAxis);
}

function loadLineChart(chart)
{
    chartId = chart.id;

    tables = YUtil.Dom.getElementsByClassName('chartview-table','table',chart);
    if (tables.length == 0) return;

    var tbl = tables[0];

    series = YUtil.Dom.getElementsByClassName('chartview-datafield','span',chart);

    var columnDefs = [];
    var seriesDefs = [];
    var baseField = series[0].innerHTML;
    var baseFieldColumn = 0;

    // Column Defs
    var headers = tbl.tHead.rows[0].cells;

    columnDefs = buildColumns(headers, false, false, null).columnDefs;

    // Just loop to get the position (j)
    for(var j = 0; j < columnDefs.length && columnDefs[j].key != series[0].innerHTML; j++) void(0);

    baseFieldColumn = j;

    div = tbl.parentNode;
    divCheckboxes = document.createElement('div');

    for (var i = 1; i < series.length; i++)
    {
        // Just loop to get the position (j)
        for(var j = 0; j < columnDefs.length && columnDefs[j].key != series[i].innerHTML; j++) void(0);
        
        var chk = document.createElement('input');
        chk.type = 'checkbox';
        chk.title = columnDefs[j].label.replace(/&nbsp;/g,' ');
        chk.value = series[i].innerHTML;
        chk.index = j;
        chk.className = 'chartview-checkbox';
        span = document.createElement('span');
        span.innerHTML += chk.title;
        divCheckbox = document.createElement('div');
        divCheckbox.className = 'chartview-checkbox-container';
        divCheckbox.appendChild(chk);
        divCheckbox.appendChild(span);
        divCheckboxes.appendChild(divCheckbox);
        
        chk.checked = true;

        YUtil.Event.on(chk, 'click', function(){
            controls[chartId].setSeriesStylesByIndex(this.index - 1,{visibility:this.checked?"visible":"hidden"});
        });
    }

    maxValue = 0;
    var rows = tbl.tBodies[0].rows;
    
    for (var i = 0; i < rows.length; i++)
    {
        for (var j = 0; j < rows[i].cells.length - 1; j++)
        {
            if (j == baseFieldColumn) continue;

            maxValue = Math.max(maxValue, parseInt(rows[i].cells[j].innerHTML));
        }
    }

    // DataSource
    var dataSource = new YUtil.DataSource(tbl);
    dataSource.responseType = YUtil.DataSource.TYPE_HTMLTABLE;
    dataSource.responseSchema = { fields: columnDefs };

    controls[chartId] = new YAHOO.widget.LineChart(div, dataSource, {
        xField: baseField,
        series: seriesDefs,
        style: {
            xAxis: { labelRotation: -90 },
            legend: { display: 'right' }
        },
        height: 300
    });

    div.appendChild(divCheckboxes);
    
    var yAxis = new YAHOO.widget.NumericAxis(); 
    yAxis.minimum = parseInt(maxValue) + 1;
    controls[chartId].set("yAxis", yAxis);

    chkboxes = YUtil.Dom.getElementsByClassName('chartview-checkbox', 'input', chartId);
    series = [];
    for(i = 0; i < chkboxes.length; i++)
    {
        if(chkboxes[i].checked)
        {
            series.push({
                displayName:chkboxes[i].title,
                yField:chkboxes[i].value
            });
        }
    }

    controls[chartId].set('series', series);
}


YUtil.Event.onDOMReady(function() { loadFormHandler = loadCharts; loadFormHandler(); });
