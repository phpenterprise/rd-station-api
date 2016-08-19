# RD Station API for Metrics
API de integração simplificada com a plataforma RD Station

### Requísitos 

* PHP 5.3 ou superior
* Extensões do PHP "php_curl" e "php_openssl"
* Apache 2.2+


### Download via composer   

    composer require phpenterprise-dev/rd-station-api:dev-master

### Exemplo de uso

~~~.php

// include lib
require '../src/rdstation/api.php';

// authentication (set your RD Station user)
$api = New \RdStation\Api('{user@example.com}', '{pass}', '{session_key:optional}');

// call method (for get leads totals) 
$a = $api->getMetrics();
        
// debug
var_dump($a);

# example of response
# int(1403)

~~~

### Instância

* Api (user, pass, session_key)

| Parâmetro     | tipo         |  descrição  |
| ------------- | ------------- | ------------- |
| user          | string       | email do usuário RD Station
| senha         | string       | senha do usuário
| session_key   | interger     | chave da sessão (opcional)

A chave da sessão possibilida multiplas sessões abertas.

### Métodos

* API : getMetrics

  Retorna o total da base de leads
  
* API : logout

  Fecha a sessão do usuário

* API : exportLeads

  Exporta todas as leads armazenadas na base

### Atualização regular.

@Release 1.0

Nota da versão: API Experimental
