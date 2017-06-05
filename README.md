# RD Station API
API de integração simplificada com a plataforma RD Station (não oficial)

[![GPL Licence](https://badges.frapsoft.com/os/gpl/gpl.svg?v=103)](https://opensource.org/licenses/GPL-3.0/) [![PHPPackages Rank](http://phppackages.org/p/smartdealer/sdapi/badge/rank.svg)](http://phppackages.org/p/phpenterprise-dev/rd-station-api) ![](https://reposs.herokuapp.com/?path=phpenterprise-dev/rd-station-api&style=flat)

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
  
* API : getVisitors

  Retorna o total de visitas no mês
  
* API : exportLeads

  Exporta todas as leads armazenadas na base
  
* API : logout

  Fecha a sessão do usuário

### Atualização regular.

@Release 1.0

Nota da versão: API Experimental
