# InterAdmin - ORM

[![Codeship Status for jp7internet/interadmin-orm](https://app.codeship.com/projects/499ecbb0-6e29-0134-13c6-7239a098062c/status?branch=master)](https://app.codeship.com/projects/177757)
[![Test Coverage](https://codeclimate.com/repos/57f6a615e61159361f001150/badges/0c21df38f69c1c472f33/coverage.svg)](https://codeclimate.com/repos/57f6a615e61159361f001150/coverage)
[![Code Climate](https://codeclimate.com/repos/57f6a615e61159361f001150/badges/0c21df38f69c1c472f33/gpa.svg)](https://codeclimate.com/repos/57f6a615e61159361f001150/feed)
[![Issue Count](https://codeclimate.com/repos/57f6a615e61159361f001150/badges/0c21df38f69c1c472f33/issue_count.svg)](https://codeclimate.com/repos/57f6a615e61159361f001150/feed)

## Description

InterAdmin ORM. Before version 3.2 it was a part of [jp7internet/classes](https://github.com/jp7internet/classes).

The API is heavily inspired by Laravel Eloquent and most methods are available here too: https://laravel.com/docs/5.3/eloquent

* [CHANGELOG](CHANGELOG.md)

## Docs

* https://wiki.jp7.com.br:81/jp7/ORM
* https://wiki.jp7.com.br:81/jp7/ORM:Cheat_Sheet
* [Extending Record and Type](https://github.com/jp7internet/interadmin-orm/wiki/Extending-Record-and-Type)

### where 
* Array - Array de condições, similar ao SQL WHERE. 

Exemplo:
```php
$registros = Registro::where(array('nome'=> 'Teste')) // Usando array como hash, equivalente a nome = 'Teste'
    ->where('valor', '>', 100) // Usando parametros
    ->where('valor', '=', 150)
    ->where('valor', 150) // O igual (=) é opcional
    ->get();

// Adicionando condição
$query = Registro::query();
if (Input::get('teste')) {
    $query->where(array('nome'=> 'Teste'));
}
$query->where('valor > 100');
$query->where('valor', 150);

$registros = $query->get();
```

#### Com relacionamentos 
* É possível se filtrar por um valor de um relacionamento utilizando o ponto (.):

```php
// Trazer Escolas da cidade de São Paulo
$escolas = Escola::where('cidade.nome', 'São Paulo')->get()
```
```sql
# equivalente ao SQL (supondo que 'cidade' é 'select_1'):
SELECT *
FROM {tabela} AS main
INNER JOIN {tabela} AS cidade
    ON cidade.id = main.select_1
WHERE cidade.varchar_key = 'São Paulo'
```

#### Com children 

* Os valores dos childrens só podem ser usados no 'where' como filtro, para se obter os valores é necessário chamar children() no objeto.

```php
// Trazer Noticias que tenham ao menos 1 filho chamado Vídeos e que seja do pais Austrália:
$noticias = Noticia
    ::where('videos.pais', $australia)
    ->get();
));
// Caso seja necessário obter o nome dos Vídeos, por exemplo:
$noticia = $noticias->first();
$videos = $noticia
    ->videos()
   ->where('pais', $australia) // É necessário repetir o filtro se não quiser vídeos de outro país
   ->get();
```

#### Verificando existência de relacionamento 

* Com uma tag qualquer

```php
Registro::has('tags');
```

* Irá resultar em 
```sql
SELECT * 
FROM registro AS main
WHERE EXISTS (SELECT * FROM tags WHERE tags.id = main.id)
```

* Com ao menos uma tag to tipo solicitado:

```php
Registro::whereHas('tags', [=> 13]('id_tipo'));
```

* Irá resultar em 
```sql
SELECT * 
FROM registro AS main
WHERE EXISTS (SELECT * FROM tags WHERE tags.id_tipo = 13 AND tags.id = main.id)
```

* Com nenhuma tag:
```php
Registro::whereDoesntHave('tags');
```

### orderBy 
* A ordem padrão é definida automaticamente pelo cadastramento dos campos no InterAdmin, mas é possível alterar:

```php
Registro::orderBy('nome')->orderBy('idade', 'DESC')->get();
```

### groupBy 

```php
Registro::groupBy('nome')->get();
```
* Irá resultar em 
```sql
SELECT * 
FROM registro AS main
GROUP BY main.varchar_key
```

### limit 

```php
Registro::skip(20)->take(10)->get();
```

### published 

Filtra por registros publicados (com mostrar, data de publicação no passado e não deletados).

* Bool - Se estiver '''TRUE''' os filtros serão usados, se estiver '''FALSE''' eles não serão usados
    * O filtro procura registros com o campo '''char_key''' marcado, '''publish''' marcado, '''deleted''' desmarcado, '''date_expire''' superior à data atual ou vazio, e '''date_publish''' inferior à data atual.
* Se esse campo não for passado para o options, então será usado o valor padrão que é obtido através de '''InterAdmin::isPublishedFiltersEnabled()'''.
    * Esse valor padrão geralmente é TRUE no site e FALSE no InterAdmin, a menos que tenha sido alterado.

```php
Registro::published(false)->get();
```

### debug 
* Boolean - Exibe o SQL que foi executado. Exemplo:
```php
Registro::debug()->get(); 
```
* Irá dar output no HTML com o SQL que foi executado.

### joins 

* Opção para incluir Joins customizados

```php
$audiences = Audience
    ::select('nome', 'COUNT(id)')
    // também existem leftJoin() e rightJoin(), esse é o INNER JOIN
    ->join('material', 'ClasseMaterial', 'FIND_IN_SET(id, material.audiences)') 
    ->groupBy('id')
    ->get();
```

### Exemplos de uso 


```php
$celtasUsados = Carro
    ::where('modelo', 'Celta')
    ->where('ano', '<',  date('Y'))
    ->orderBy('ano', 'desc')
    ->orderBy('preco') // Os mais novos e mais baratos primeiro
    ->take(20) // Página com 20 itens
    ->get(); // Procura todos, first() procura somente o primeiro.
```


### Queries parciais 

```php
$query = Carro::query();

if ($param[{ // parâmetro recebido
    $query->where(['modelo' => $param['modelo']('modelo']))]);
}
if ($param['usado']) {
    $query->whereRaw("ano < YEAR(NOW())");
}

$carros = $query->get();
```

### Escopos pré-definidos 

```php
class Carro extends InterAdmin {
    public function scopeUsados($query) {
       return $query->whereRaw('ano < YEAR(NOW())');
    }
    public function scopeCeltas($query) {
       return $this->where('modelo', 'Celta');
    }
    public function scopePagina($query, $n) {
       return $query->skip(($n - 1) * 20)->take(20);
    }
}

$carros = Carro::celtas()->usados()->pagina(1)->get();
```

### Campos dos registros filhos no where 
* Obter todos os blogs que contenham ao menos um post publicado, trazendo os assuntos já carregados:

```php
$blogs = Blog::where('posts.publicado', true)->get();
```

### Ordenar pela contagem dos registros filhos  

```php
$noticias = Noticia
    ::select('COUNT(arquivos_para_download.id) AS arquivos_count', 'nome'),
    ->orderByRaw('`arquivos_count` DESC') // necessario usar acento grave para aliases
    ->get();
```


## Mass Assignment

Just like Laravel Eloquent, the create($array) and fill($array) methods can only receive attributes listed on getFillable() - whitelisted attributes.

* getFillable() - by default returns the fields checked as 'form' on InterAdmin.

You can temporarily disable mass assignment protection when data is safe, on seeds or tests, for example:

```php
\Jp7\Interadmin\Record::unguard();

Classe::create(['idade' => 12, 'nome' => 'teste']);

\Jp7\Interadmin\Record::reguard();
```


## Docs for v2.* versions

* https://wiki.jp7.com.br:81/jp7/index.php?title=ORM&oldid=4164
* https://wiki.jp7.com.br:81/jp7/index.php?title=ORM:Query&oldid=4274


## Tests

```
cp .env.example .env.testing
php vendor/bin/codecept run --coverage
```
