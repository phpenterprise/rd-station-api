<?php

/*
 * The example file
 * @author: Patrick Otto <patrick@phpenterprise.com>
 * 
 * For experimental use, rights reserved for Resultados Digitais
 * 
 */

require '../source/rdapi.class.php';

// set email and pass
$api = New \RdStation\Api('{user@example}','{pass}');

// call method for get lead totals 
$a = $api->getMetrics();
        
// debug
var_dump($a);

# example of responses
# int(1403)

