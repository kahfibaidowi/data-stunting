<?php


function null_to_empty($var)
{
    if(is_null($var)){
        return "";
    }
    return $var;
}
function is_image_file($string)
{
    $upload_path=storage_path(env("UPLOAD_PATH"));
    
    if(trim($string)==""){
        return false;
    }
    if(file_exists($upload_path."/".$string)){
        $file_info=new \finfo(FILEINFO_MIME_TYPE);
        $file_show=file_get_contents($upload_path."/".$string);

        $extensions=['image/jpeg', 'image/jpg', 'image/png'];
        if(in_array($file_info->buffer($file_show), $extensions)){
            return true;
        }
        return false;
    }
    return false;
}
function is_document_file($string)
{
    $upload_path=storage_path(env("UPLOAD_PATH"));
    
    if(trim($string)==""){
        return false;
    }
    if(file_exists($upload_path."/".$string)){
        $file_info=new \finfo(FILEINFO_MIME_TYPE);
        $file_show=file_get_contents($upload_path."/".$string);

        $extensions=[
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        if(in_array($file_info->buffer($file_show), $extensions, true)){
            return true;
        }
        return false;
    }
    return false;
}
function add_columns_to_array($array=[], $columns=[])
{
    $data=[];
    foreach($array as $val){
        $data[]=array_merge($val, $columns);
    }

    return $data;
}
function array_with_columns($array=[], $columns)
{
    $data=[];
    foreach($array as $val){
        $col=[];
        foreach($columns as $column){
            $col[$column]=$val[$column];
        }

        $data[]=$col;
    }

    return $data;
}
function array_find_by_key($array=[], $key, $value)
{
    foreach($array as $element){
        if($element[$key]==$value){
            return $element;
        }
    }
    return false;
}
function array_merge_without($array, $without=[], $merge=[])
{
    $new_array=$array;
    foreach($without as $w){
        if(isset($new_array[$w])) unset($new_array[$w]);
    }

    return array_merge($new_array, $merge);
}
function ceil_with_enclosure($number, $enclosure=0.5)
{
    $int=floor($number);

    if($number<=$int+$enclosure){
        return floor($number);
    }
    else{
        return round($number);
    }
}
function count_day($start, $end, $with_one=false)
{
    $time_start=strtotime($start);
    $time_end=strtotime($end);
    
    return (($time_end-$time_start)/(24*3600))+($with_one?1:0);
}
function count_month($start, $end, $with_one=false){
    $date1 =$start;
    $date2=$end;
    
    $ts1=strtotime($date1);
    $ts2=strtotime($date2);
    
    $year1=date('Y', $ts1);
    $year2=date('Y', $ts2);
    
    $month1=date('m', $ts1);
    $month2=date('m', $ts2);
    
    $diff=(($year2-$year1)*12)+($month2-$month1);
    
    return $with_one?$diff+1:$diff;
}