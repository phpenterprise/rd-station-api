# RD Station API for Metrics
API de integração simplificada com a plataforma RD Station

### Requísitos 

* PHP 5.3 ou superior
* Extensões do PHP "php_curl" e "php_openssl"
* Apache 2.2+


### Chamada via composer

    composer require phpenterprise-dev/rd-station-api

### Exemplo de uso

~~~.php

// include lib
require '../src/rdstation/api.php';

// authentication (set your RD Station user)
$api = New \RdStation\Api('{user@example.com}','{pass}');

// call method (for get leads totals) 
$a = $api->getMetrics();
        
// debug
var_dump($a);

# example of response
# int(1403)

~~~

### Métodos

* API : getMetrics

  Retorna o total da base de leads

### Atualização regular.

@Release 1.0

Nota da versão: API Experimental
