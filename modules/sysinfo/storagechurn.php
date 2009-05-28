<?php
/**
 * Create a graph of files-pet-minute by analyzing storage.log
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2008
 * @license Licensed under GNU General Public License v2.0. See file license.txt
 *
 * @todo add support for user-selected start and end date
 * @todo support coarser intervals than 60 secs
 * @todo add a caching layer or create the graph inline instead of stocking it on disk
 * @todo improve layout: graph padding, x axis labels, etc...
 */

// *** Parse storage.log file ***

$scale = 60;
$scalenames = array( 60 => 'minute', 60*60 => 'hour', 60*60*24 => 'day' );

$ini = eZINI::instance();
$file =  file( eZSys::varDirectory() . '/' . $ini->variable( 'FileSettings', 'LogDir' ) . '/storage.log' ); // nb: this dir is calculated the same way as ezlog does
$data = array();
foreach ( $file as $line )
{
    $time = strtotime( substr( $line, 2, 20 ) );
    $time = $time - ( $time % $scale );
    if( !isset( $data[$time] ) )
    {
        $data[$time] = 1;
    }
    else
    {
        $data[$time]++;
    }
}
$times = array_keys( $data );
$min = $times[0];
$max = end( $times );
/*
   for ( $i = $min; $i <= $max; $i+= $scale )
   {
   $nodata[$i] = 0;
   }
   $data = array_merge( $nodata, $data );
*/

// *** build graph ***

$graphname = ezi18n( 'SysInfo', 'Files per '.$scalenames[$scale] );

$graph = new ezcGraphBarChart();
$graph->title = ezi18n( 'SysInfo', 'Storage churn' );
$graph->palette = new ezcGraphPaletteEzBlue();
$graph->yAxis->label = $graphname;
$graph->legend = false;
// width of bar charts is not calculated correctly by DateAxis
//$graph->xAxis = new ezcGraphChartElementDateAxis();
//$graph->xAxis->interval = $scale;
$graph->xAxis = new ezcGraphChartElementNumericAxis();
$graph->xAxis->min = $min;
$graph->xAxis->max = $max;
$graph->xAxis->labelCallback = 'calcChurnLabel';
function calcChurnLabel( $pos, $step )
{
    $locale = eZLocale::instance();
    $out = $locale->formatShortDatetime( $pos );
    return $out;
}
$graph->data[$graphname] = new ezcGraphArrayDataSet( $data );
$graph->driver = new ezcGraphGdDriver();
$graph->driver->options->imageFormat = IMG_JPEG;
$graph->options->font = 'c:/windows/fonts/arial.ttf'; /// @todo

$outputfile = eZSys::storageDirectory() . '/sysinfo/storagestats.jpg';
$graph->render( 600, 400,  eZSys::rootDir() . '/' . $outputfile );

// *** output ***

require_once( "kernel/common/template.php" );
$tpl = templateInit();
$tpl->setVariable( 'title', 'Storage churn' );
$tpl->setVariable( 'graphsource', $outputfile );

$Result = array();
$Result['content'] = $tpl->fetch( "design:sysinfo/storagechurn.tpl" ); //var_dump($cacheFilesList);

$Result['left_menu'] = 'design:parts/sysinfo/menu.tpl';
$Result['path'] = array( array( 'url' => false,
                                'text' => ezi18n( 'SysInfo', 'Storage churn' ) ) );
?>