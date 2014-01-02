<?php
// convert huge_one.mbox file to many.eml files.
// Author uzulla <http://twitter.com/uzulla>

// strict error bailout
function strict_error_handler($errno, $errstr, $errfile, $errline)
{
    die ("STRICT: {$errno} {$errstr} {$errfile} {$errline} ".PHP_EOL);
}
set_error_handler("strict_error_handler");

// check arg.
if(!isset($argv[2]))
    die ("usage: this.php from.mbox to_dir/");

$from_file = $argv[1];
$to_dir = $argv[2];
$skip_header_line = (isset($argv[3])) ? (int)$argv[3] : 0;

// open .mbox file
$fh = fopen($from_file, "r");
if(!$fh)
    die ("can't open from file.");

// writing....
$counter = 0;
while($line = fgets($fh)){
    if(preg_match('/^From /', $line)){
        if($counter++ % 100 === 0)
            echo '.';
        // start segment. create new file.
        $oh = new_output_file_handle($line, $to_dir);

        // Skip gmail Special Header, UGLY...
        for($i=0; $i<$skip_header_line; $i++){
            fgets($fh);
        }
        continue;
    }
    if($oh){ // if false, skip.
        // unescape indented '>From ' to 'From '
        preg_replace('/^>([>]*)From /', "$1From ", $line);
        fwrite($oh, $line);
    }
}

function new_output_file_handle($line, $prefix_dir='./'){
    $list = preg_split('/ /', $line, 3);
    $id = $list[1];
    $time = strtotime($list[2]);
    $filename = date("YmdHis", $time)."_".$id.".eml";
    $to_dir = $prefix_dir."/". date("Y-m", $time).'/';

    if(file_exists($to_dir)){
        if(!is_dir($to_dir))
            die ("{$to_dir} is file exists. directory create fail.".PHP_EOL);
    }else{
        mkdir($to_dir);
    }

    if(!file_exists($to_dir.$filename)){
        return fopen($to_dir.$filename, "w");
    }else{
        echo "{$to_dir}/{$filename} is exists. skipped.".PHP_EOL;
        return false;
    }
}

