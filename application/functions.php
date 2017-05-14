<?php

function p($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
function p_e($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit;
}

function getIncludedFiles(){
    $included_files = get_included_files();
    return $included_files;
}
