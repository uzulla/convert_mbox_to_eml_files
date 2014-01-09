<?php
// convert huge_one.mbox file to many.eml(or .emlx) files.
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
$emlx_flag = (isset($argv[4])) ? (int)$argv[4] : 0;

// open .mbox file
$fh = fopen($from_file, "r");
if(!$fh)
    die ("can't open from file.");

// writing....
$counter = 0;
$current_file_name = null;
while($line = fgets($fh)){
    if(preg_match('/^From /', $line)){
        if($counter++ % 100 === 0)
            echo '.';

        if(isset($oh)&&$oh)
            fclose($oh);

        if(!is_null($current_file_name) && $emlx_flag){// convert emlx
            $emlrh = fopen($current_file_name, 'r');
            $emlxwh = fopen($current_file_name."x", "w");
            
            fwrite($emlxwh, (filesize($current_file_name)+2)."\n" );
            while($eml_line = fgets($emlrh)){
                fwrite($emlxwh, $eml_line);
            }
            fwrite($emlxwh, "\n\n".'<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
<key>date-sent</key>
<real></real>
<key>flags</key>
<integer></integer>
<key>sender</key>
<string></string>
<key>subject</key>
<string></string>
<key>to</key>
<string></string>
</dict>
</plist>');
            unlink($current_file_name);
            fclose($emlrh);
            fclose($emlxwh);
        }

        // start segment. create new file.
        list($oh, $_filename ) = new_output_file_handle($line, $to_dir);
        $current_file_name = $_filename;

        // Skip gmail Special Header, UGLY...
        for($i=0; $i<$skip_header_line; $i++){
            fgets($fh);
        }
        continue;
    }
    if($oh){ // if false, skip.
        // unescape indented '>From ' to 'From '
        $line = preg_replace('/^>([>]*)From /', "$1From ", $line);
        if($emlx_flag) // CRLF to LF, I think .emlx is must LF.
            $line = preg_replace("/\r/", "", $line);
        fwrite($oh, $line);
    }
}
echo PHP_EOL."create {$counter} eml files.".PHP_EOL;

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
        return [fopen($to_dir.$filename, "w"), $to_dir.$filename];
    }else{
        echo "{$to_dir}/{$filename} is exists. skipped.".PHP_EOL;
        return [false, null];
    }
}

