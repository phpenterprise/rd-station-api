<?php

/*
 * The example file
 * @author: Patrick Otto <patrick@phpenterprise.com>
 * 
 * For experimental use, rights reserved for Resultados Digitais
 * 
 */

require '../src/RdStation/Api.php';

// set email and pass
$api = New \RdStation\Api('REPLACE_ACCOUNT_MAIL', 'REPLACE_ACCOUNT_PASSWORD');

// call method for get lead totals 
$a = $api->getMetrics();

// debug
var_dump($a);

# example of response
# int(1500)

