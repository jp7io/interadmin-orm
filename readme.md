# InterAdmin - ORM

[![Codeship Status for jp7internet/interadmin-orm](https://app.codeship.com/projects/499ecbb0-6e29-0134-13c6-7239a098062c/status?branch=master)](https://app.codeship.com/projects/177757)
[![Test Coverage](https://codeclimate.com/repos/57f6a615e61159361f001150/badges/0c21df38f69c1c472f33/coverage.svg)](https://codeclimate.com/repos/57f6a615e61159361f001150/coverage)
[![Code Climate](https://codeclimate.com/repos/57f6a615e61159361f001150/badges/0c21df38f69c1c472f33/gpa.svg)](https://codeclimate.com/repos/57f6a615e61159361f001150/feed)
[![Issue Count](https://codeclimate.com/repos/57f6a615e61159361f001150/badges/0c21df38f69c1c472f33/issue_count.svg)](https://codeclimate.com/repos/57f6a615e61159361f001150/feed)

## Description

InterAdmin ORM. Before version 3.2 it was a part of [jp7internet/classes](https://github.com/jp7internet/classes).

The API is heavily inspired by Laravel Eloquent and most methods are available here too: https://laravel.com/docs/5.3/eloquent

* [CHANGELOG](CHANGELOG.md)

## Docs v3.*

* https://wiki.jp7.com.br:81/jp7/ORM
* https://wiki.jp7.com.br:81/jp7/ORM:Query
* [Extending a Type](https://github.com/jp7internet/interadmin-orm/wiki/Extending-a-Type)
* https://wiki.jp7.com.br:81/jp7/ORM:Cheat_Sheet

## Docs v2.*

* https://wiki.jp7.com.br:81/jp7/index.php?title=ORM&oldid=4164
* https://wiki.jp7.com.br:81/jp7/index.php?title=ORM:Query&oldid=4274

## Mass Assignment

Ao usar create($array) ou fill($array), os campos somente serão preenchidos se estiverem na whitelist fornecida pelo método getFillable().

### getFillable

* O método getFillable() só retorna os campos que estiverem com 'form' marcado no InterAdmin.

### unguard e reguard

* É possível desativar temporariamente a proteção de mass assignment:

```php
\Jp7\Interadmin\Record::unguard();

Classe::create(['idade' => 12, 'nome' => 'teste']);

\Jp7\Interadmin\Record::reguard();
```



## Tests

```
cp .env.example .env.testing
php vendor/bin/codecept run --coverage
```
