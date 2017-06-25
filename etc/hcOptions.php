<?php
 
// Default Chart Options
$hcOptions->chart->zoomType = "x";
$hcOptions->chart->spacingRight = 20;
$hcOptions->chart->type = "line";
$hcOptions->title->text = "Chart Title";
$hcOptions->subtitle->text = "(please set this in your module)";
$hcOptions->legend->radius = 3;
$hcOptions->legend->align = "center";
$hcOptions->legend->verticalAlign = "bottom";
$hcOptions->xAxis->type = "datetime";
$hcOptions->xAxis->maxZoom = 2*3600000;
$hcOptions->yAxis->startOnTick = false;
$hcOptions->yAxis->showFirstLabel = false;
$hcOptions->tooltip->formatter = "function() { return '<b>'+ this.series.name +'</b><br/>'+ Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) +'<br/>'+ Highcharts.numberFormat(this.y, 4); }";

// Default Plot Options
// line
$hcOptions->plotOptions->line->lineWidth = 1.6;
$hcOptions->plotOptions->line->animation = false;
$hcOptions->plotOptions->line->step = true;
$hcOptions->plotOptions->line->shadow = false;
$hcOptions->plotOptions->line->stacking = '';
$hcOptions->plotOptions->line->marker->enabled = false;
// spline
$hcOptions->plotOptions->spline->lineWidth = 1.6;
$hcOptions->plotOptions->spline->animation = false;
$hcOptions->plotOptions->spline->step = true;
$hcOptions->plotOptions->spline->shadow = false;
$hcOptions->plotOptions->spline->stacking = '';
$hcOptions->plotOptions->spline->marker->enabled = false;
// area
$hcOptions->plotOptions->area->lineWidth = 1;
$hcOptions->plotOptions->area->animation = false;
$hcOptions->plotOptions->area->step = true;
$hcOptions->plotOptions->area->shadow = false;
$hcOptions->plotOptions->area->stacking = 'normal';
$hcOptions->plotOptions->area->marker->enabled = false;

?>
