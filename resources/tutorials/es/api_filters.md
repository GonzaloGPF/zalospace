---
title: 'Una API de listados con filtros genéricos en Laravel'
description: Cómo dar a todos los listados de una API de Laravel los mismos filtros desde la URL (rangos, exclusiones, relaciones, conteos, orden y paginación) con una clase base sobre EloquentFilter, y qué hay que cerrar para que sea segura.
---

# Una API de listados con filtros genéricos en Laravel

## Introducción {#intro}

En [Talent Battles](https://talentbattles.com) hay más de veinte listados: tracks, diseños, batallas, usuarios, comentarios, pedidos, notificaciones... Todos salen del mismo tipo de endpoint, y el frontend pide en la URL lo que necesita:

```
GET /api/tracks?track_types[]=beat&min_bpm=80&max_bpm=100&not_id[]=12&with[]=createdBy&counts[]=likes&sort=created_at|desc&per_page=20
```

Es decir: los beats de entre 80 y 100 BPM, sin el 12, con su autor y su número de likes, del más nuevo al más antiguo y de 20 en 20.

Y el controlador de cada listado tiene siempre las mismas dos líneas:

```php title="app/Http/Controllers/TrackController.php"
public function index(): JsonResponse
{
    $tracks = Track::filter(request()->all())
        ->getOrPaginate();

    return $this->indexResponse(TrackResource::collection($tracks));
}
```

Cada modelo tiene su propia clase de filtros, solo con lo que es suyo, como "los tracks en venta". Todo lo demás (rangos, exclusiones, enums, relaciones, conteos, orden y paginación) viene de una clase base que comparten todos.

### El porqué de este tutorial

Por dos motivos:

- **No repetir código.** Un modelo nuevo tiene todas las convenciones desde el primer día, y el frontend pide cualquier listado de la misma forma.
- **La seguridad.** Una API que acepta nombres de columnas y de relaciones desde la URL es una puerta a la base de datos. La clase base decide qué puede llegar a la consulta y qué no. Buena parte del tutorial va de eso, incluido un fallo que encontré escribiéndolo.

## Requisitos {#requirements}

- [Laravel](https://laravel.com/). Uso Laravel 13 con PHP 8.4.
- [EloquentFilter](https://github.com/Tucker-Eric/EloquentFilter) (`tucker-eric/eloquentfilter`). Uso la versión 3.6.

```bash
composer require tucker-eric/eloquentfilter
php artisan vendor:publish --provider="EloquentFilter\ServiceProvider"
```

En la configuración publicada solo cambio la carpeta de los filtros:

```php title="config/eloquentfilter.php"
'namespace' => 'App\\Filters\\',
```

## Cómo funciona EloquentFilter {#eloquentfilter}

EloquentFilter se encarga de lo básico:

- El modelo usa el trait `Filterable`, que le da el scope `filter()`.
- `Track::filter($input)` crea un `App\Filters\TrackFilter`: la carpeta de la configuración, el nombre del modelo y `Filter`.
- Por cada clave de la entrada, llama al método del filtro con ese nombre en camelCase: `can_challenge=1` llama a `canChallenge(1)`. Si la clave termina en `_id`, lo quita: `album_id=3` llama a `album(3)`.
- Las claves sin método se ignoran, y también los valores vacíos (`''`, `null` o `[]`).

Dentro de un método, `$this->where()`, `$this->whereHas()` y compañía van directos a la consulta. Así son los filtros propios de los tracks:

```php title="app/Filters/TrackFilter.php"
class TrackFilter extends Filter
{
    public function promoted(mixed $value): void
    {
        if ($value) {
            $this->whereHas('product', fn (Builder $q) => $q->where('promoted', '>=', today()));
        }
    }

    public function canChallenge(mixed $value): void
    {
        $this->boolean('can_challenge', $value);
    }

    public function inSale(mixed $value): void
    {
        $value
            ? $this->whereHas('product')
            : $this->whereDoesntHave('product');
    }

    public function genres(mixed $values): void
    {
        collect(Arr::wrap($values))
            ->each(fn (string $value) => $this->where('genres', 'like', "%$value%"));
    }

    // ...
}
```

Hasta aquí, todo es del paquete. Lo que viene a continuación es la clase base `Filter`, que hereda de `ModelFilter` y añade las convenciones comunes y la seguridad.

## El modelo y la paginación {#model}

El modelo usa un trait propio, `IsFilterable`, que incluye el `Filterable` del paquete y añade `getOrPaginate()`:

```php title="app/Traits/IsFilterable.php"
<?php

namespace App\Traits;

use EloquentFilter\Filterable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

trait IsFilterable
{
    use Filterable;

    public int $defaultPerPage = 15;

    public int $maxLimit = 1000;

    /**
     * The first `limit` records, or a page of `per_page` records. A limit or a page size
     * outside 1 to maxLimit is ignored, and so is a page that is not a positive number.
     *
     * @param  Builder<self>  $query
     * @return Collection<int, self>|LengthAwarePaginator<int, self>
     */
    public function scopeGetOrPaginate(Builder $query): Collection|LengthAwarePaginator
    {
        $limit = request()->integer('limit');

        if ($this->isWithinMaxLimit($limit)) {
            return $query->limit($limit)->get();
        }

        $perPage = request()->integer('per_page');
        $page = max(1, request()->integer('page', 1));

        $paginator = $query->paginate($this->isWithinMaxLimit($perPage) ? $perPage : $this->defaultPerPage, ['*'], 'page', $page);

        $paginator->appends($this->filtered);

        return $paginator;
    }

    private function isWithinMaxLimit(int $value): bool
    {
        return $value >= 1 && $value <= $this->maxLimit;
    }
}
```

- Con `limit`, devuelve los primeros registros sin paginar. Sirve para cosas como "los 5 últimos comentarios".
- Si no, pagina con `per_page` (15 por defecto) y `page`.
- Tanto `limit` como `per_page` tienen un máximo, `maxLimit`. Sin él, `?per_page=1000000` pediría la tabla entera de una vez.
- `appends($this->filtered)` añade los filtros a los enlaces de la paginación, así la página 2 mantiene los mismos filtros que la 1. `filtered` lo guarda el paquete: es la entrada que se ha usado, sin los valores vacíos.

Cualquier modelo que use el trait tiene ya `filter()` y `getOrPaginate()`:

```php
class Track extends Model
{
    use IsFilterable;
}
```

## Las convenciones comunes {#conventions}

La clase base sobrescribe `handle()`, el método que aplica el filtro:

```php title="app/Filters/Filter.php"
public function handle(): Builder
{
    $query = parent::handle();

    $this->applyRanges();

    $this->applyNotFilters();

    $this->applyRelations();

    $this->applyConstants();

    $this->applyCounts();

    $this->applySorting();

    $this->applyLimit();

    return $query;
}
```

`parent::handle()` es el del paquete: llama a `setup()` y a los métodos de cada clave. Después, la clase base aplica las convenciones que valen para cualquier modelo:

| Parámetro | Ejemplo | Qué hace |
| :- | :- | :- |
| `min_` o `max_` y la columna | `min_price=10&max_price=50` | Un rango, con `>=` y `<=` |
| `not_` y la columna | `not_name[]=Ana` | Excluye esos valores |
| El nombre del enum | `track_types[]=beat` | Filtra una columna con cast a ese enum |
| `with` | `with[]=createdBy.image` | Carga relaciones |
| `counts` | `counts[]=likes` | Cuenta relaciones |
| `sort` | `sort=name|desc` | Ordena por una columna |
| `randomize` | `randomize=1` | Orden aleatorio |
| `limit` | `limit=5` | Solo los primeros, sin paginar |
| `per_page` y `page` | `per_page=20&page=2` | Paginación |

### Rangos

```php title="app/Filters/Filter.php"
private function applyRanges(): void
{
    collect(array_keys($this->input))
        ->filter(fn (string $input) => Str::startsWith($input, [self::MIN_PREFIX, self::MAX_PREFIX]) && ! method_exists($this, Str::camel($input)))
        ->map(fn (string $input) => $this->applyRange($input, $this->input[$input]));
}

private function applyRange(string $input, mixed $value): void
{
    // Clean up prefixes to determine the table's column name
    $column = str_replace(self::MIN_PREFIX, '', str_replace(self::MAX_PREFIX, '', $input));

    if (! $this->canUseColumn($column)) {
        return;
    }

    $operator = Str::startsWith($input, self::MIN_PREFIX)
        ? '>='
        : '<=';

    if (isDateString($value)) {
        try {
            $date = Carbon::parse($value);
            $dateString = $operator === '>='
                ? $date->startOfDay()->toDateTimeString()
                : $date->endOfDay()->toDateTimeString();

            $this->query->where($column, $operator, $dateString);
        } catch (Exception) {
            // A date that cannot be read leaves the range unfiltered
        }
    } else {
        $this->query->where($column, $operator, (float) $value);
    }
}
```

- La columna es la clave sin el prefijo: `min_price` filtra `price`.
- Con una fecha, el mínimo empieza a las 00:00 y el máximo termina a las 23:59:59. Así `max_created_at=2026-11-28` incluye todo ese día, y no solo su primer segundo.
- Cualquier otro valor se convierte en número.
- Si el filtro de un modelo tiene su propio método, como `minPrice()`, el rango genérico no se aplica a esa clave. Cada modelo puede cambiar una convención sin tocar la base.

`canUseColumn()` decide qué columnas se pueden usar desde la URL. Lo explico en [Seguridad](#security).

### Exclusiones

```php title="app/Filters/Filter.php"
private function applyNotFilter(string $input, mixed $value): void
{
    // Clean up prefixes to determine the table's column name
    $column = str_replace(self::NOT_PREFIX, '', $input);

    if (! $this->canUseColumn($column)) {
        return;
    }

    if (is_array($value)) {
        $this->whereIn($column, $value, 'and', true); // Where not in
    } else {
        $this->where($column, '!=', $value);
    }
}
```

Con una lista, `whereNotIn`. Con un valor suelto, `!=`.

### Enums

```php title="app/Filters/Filter.php"
private function applyConstants(): void
{
    loadConstants()
        ->map(fn (string $constantClass) => Str::snake(class_basename($constantClass)))
        ->filter(fn (string $constantName) => $this->shouldFilterConstant($constantName))
        ->each(function (string $constantName) {
            $value = $this->input[$constantName];
            $column = Str::singular($constantName);
            if (is_array($value)) {
                $this->query->whereIn($column, $value);
            } else {
                $this->query->where($column, $value);
            }
        });
}
```

`loadConstants()` es un helper que devuelve los enums de `app/Enums`. La clave es el nombre del enum en snake_case (`TrackTypes` pasa a `track_types`), y la columna, ese nombre en singular (`track_type`). Solo se aplica si esa columna del modelo tiene un cast a un enum, así que `track_types[]=beat` filtra los tracks sin escribir ningún método.

### Relaciones y conteos

```php title="app/Filters/Filter.php"
private function applyRelations(): void
{
    $relations = resolve(RelationHelper::class)->requested($this->query->getModel(), $this->input[self::WITH] ?? null);

    $this->query->with($relations);
}
```

Con `with[]=createdBy.image`, cada track llega con su autor, y el autor con su imagen. El frontend pide en cada pantalla lo que va a pintar, y el backend no tiene que adivinarlo.

`counts` funciona igual pero con `withCount()`, y admite un nivel más: `counts[]=createdBy.likes` cuenta los likes del autor de cada track.

```php title="app/Filters/Filter.php"
/**
 * A count is a relation of the model ("comments") or a relation of one of its relations ("createdBy.tracks").
 */
private function applyCounts(): void
{
    $counts = collect(resolve(RelationHelper::class)->requested($this->query->getModel(), $this->input[self::COUNT] ?? null, maxDepth: 2));

    $rootCounts = $counts
        ->reject(fn (string $path) => str_contains($path, '.'))
        ->values()
        ->all();

    $nestedCounts = $counts
        ->filter(fn (string $path) => str_contains($path, '.'))
        ->mapWithKeys(function (string $path) {
            [$relation, $countedRelation] = explode('.', $path);

            return [$relation => fn ($query) => $query->withCount($countedRelation)];
        })
        ->all();

    if (count($nestedCounts)) {
        $this->query->with($nestedCounts);
    }

    $this->query->withCount($rootCounts);
}
```

En los dos casos, los nombres pasan antes por `RelationHelper`, que solo deja pasar relaciones de verdad. Es la parte más delicada, y la explico también en [Seguridad](#security).

### Orden

```php title="app/Filters/Filter.php"
private function applySorting(): void
{
    $table = $this->query->getModel()->getTable();
    $sortString = $this->input[self::SORT] ?? '';
    [$attribute, $direction] = str_contains($sortString, '|') ? explode('|', $sortString) : [null, null];

    if ($this->input[self::RANDOMIZE] ?? false) {
        $this->query->inRandomOrder();
    } elseif ($attribute && $this->canUseColumn($attribute)) {
        $this->query->getQuery()->orders = []; // clean up any previous ordering

        $this->query->orderBy("$table.$attribute", Str::lower($direction ?? '') === 'desc' ? 'desc' : 'asc');
    } else {
        $this->query->latest("$table.created_at"); // default ordering
    }
}
```

- `sort=name|desc` ordena por `name` de mayor a menor. Una dirección desconocida se queda en ascendente.
- La columna lleva delante el nombre de la tabla. Si un filtro hace un join, `created_at` podría estar en las dos tablas y la consulta fallaría por ambigua.
- Sin `sort`, lo más nuevo primero.

## Seguridad {#security}

Todo lo anterior acepta nombres que vienen de la URL. Estas son las cinco cosas que impiden que esos nombres hagan más de lo que deben.

### Solo métodos públicos, con su nombre exacto

El paquete llama a cualquier método del filtro cuyo nombre coincida con una clave. Pero en la clase base hay métodos que no son filtros, sino ayudantes para escribirlos: `whereIn()`, `boolean()`, `nullable()`... Si `?boolean=x` llamara a `boolean('x')`, el resultado sería un error, en el mejor de los casos.

```php title="app/Filters/Filter.php"
/**
 * Each input key calls the filter method with its name. Only public methods are filters:
 * the protected and private ones are helpers, and calling them with the input would fail.
 * The key has to spell the method exactly. PHP finds a method whatever its case, but the
 * blacklist compares names exactly, so "EMAIL" would run email() even when it is blacklisted.
 *
 * @param  string  $method
 */
public function methodIsCallable($method): bool
{
    if (! parent::methodIsCallable($method)) {
        return false;
    }

    $reflection = new ReflectionMethod($this, $method);

    return $reflection->isPublic() && $reflection->getName() === $method;
}
```

La regla es sencilla: los filtros son públicos y los ayudantes, `protected` o `private`. Lo de que el nombre sea exacto lo explico un poco más abajo.

### Columnas privadas

`UserResource` solo enseña el email, la fecha de nacimiento o el teléfono al propio usuario y a los administradores. Pero ocultar un campo en el resource no basta si se puede filtrar por él: con `?email=gmail`, el listado ya no muestra los emails, pero dice qué usuarios lo tienen de Gmail. Y probando letra a letra, se puede sacar el email entero.

Por eso cada filtro puede marcar columnas privadas:

```php title="app/Filters/UserFilter.php"
/**
 * What UserResource only shows to the user and to admins, and the secrets it never shows.
 *
 * @var array<int, string>
 */
protected array $privateColumns = [
    'role', 'last_name', 'email', 'phone', 'birthdate', 'gender', 'points', 'address', 'account',
    'notification_settings', 'password', 'two_factor_secret', 'two_factor_recovery_codes', 'token',
    'remember_token', 'oauth_id', 'oauth_driver', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at',
    'claim_token', 'claim_expires_at',
];
```

Y la clase base las cierra por dos lados. `setup()` añade a la lista negra del paquete los métodos con el nombre de una columna privada, como `email()`:

```php title="app/Filters/Filter.php"
/**
 * Filter methods named after a private column are ignored for everyone but admins.
 */
protected function setup(): void
{
    collect($this->privateColumns)
        ->reject(fn (string $column) => $this->canUseColumn($column))
        ->each(fn (string $column) => $this->blacklistMethod(Str::camel($column)));
}
```

Y `canUseColumn()` las deja fuera de los rangos, las exclusiones, los enums y el orden:

```php title="app/Filters/Filter.php"
/**
 * Only a column name in snake case may reach the query. MySQL reads "EMAIL" as the column "email",
 * so any other spelling could use a private column under a name that is not on the list.
 */
private function canUseColumn(string $column): bool
{
    return preg_match('/^[a-z0-9_]+$/', $column) === 1
        && (! in_array($column, $this->privateColumns, true) || hasRole(Roles::ADMIN));
}
```

Los administradores sí pueden usarlas, porque las ven igualmente.

> [!WARNING]
> Escribiendo este tutorial me di cuenta de que la lista se podía saltar cambiando las mayúsculas. **MySQL no distingue mayúsculas en los nombres de columna**, así que `?sort=EMAIL|asc` ordenaba por el email, aunque `EMAIL` no estuviera en la lista. Y **PHP tampoco las distingue en los nombres de método**: `?EMAIL=gmail` se convertía en `eMAIL()`, que no está en la lista negra (el paquete la compara con `in_array(..., true)`), y PHP ejecutaba igualmente `email()`.
> La solución son las dos comprobaciones que has visto: la columna tiene que estar en snake_case y en minúsculas, y la clave tiene que escribir el método exactamente. Si tienes una lista de nombres prohibidos, compárala siempre con el nombre tal y como lo va a entender quien lo ejecuta.

### Solo relaciones de verdad

Eloquent carga una relación llamando al método del modelo que tiene ese nombre, sea cual sea:

```php
return $this->getModel()->newInstance()->$name();
```

Solo después comprueba si lo que ha devuelto es una relación. Así que `with[]` tiene que recibir únicamente nombres de relaciones. De eso se encarga `RelationHelper` (completo en la sección [Código](#code)):

```php title="app/Helpers/RelationHelper.php"
/**
 * The requested paths ("createdBy", "orderable.createdBy") that are relations from the model.
 * Keys, values that are not text, unknown names and paths deeper than the maximum are dropped.
 *
 * @return array<int, string>
 */
public function requested(Model $model, mixed $requested, int $maxDepth = PHP_INT_MAX): array
{
    return collect(Arr::wrap($requested))
        ->filter(fn (mixed $path) => is_string($path))
        ->map(fn (string $path) => array_map(fn (string $name) => Str::camel($name), explode('.', $path)))
        ->filter(fn (array $names) => count($names) <= $maxDepth && $this->isPath($model::class, $names))
        ->map(fn (array $names) => implode('.', $names))
        ->unique()
        ->values()
        ->all();
}

/**
 * Only a public method declared to return a relation counts, so checking a name never calls any other method.
 *
 * @param  class-string<Model>  $modelClass
 */
private function isRelationMethod(string $modelClass, string $name): bool
{
    if (! method_exists($modelClass, $name)) {
        return false;
    }

    $method = new ReflectionMethod($modelClass, $name);
    $returnType = $method->getReturnType();

    return $method->isPublic()
        && ! $method->isStatic()
        && $method->getNumberOfRequiredParameters() === 0
        && $returnType instanceof ReflectionNamedType
        && is_a($returnType->getName(), Relation::class, true);
}
```

- Comprueba cada tramo del camino: en `createdBy.image`, que `createdBy` es una relación de `Track`, y que `image` lo es del modelo al que lleva.
- Lo sabe por **reflexión**, mirando el tipo que declara el método, sin llamarlo. Por eso todas las relaciones de los modelos declaran su tipo de retorno (`: BelongsTo`, `: HasMany`...).
- Descarta las entradas con clave, como `with[createdBy]=...`. En Eloquent, el valor de una entrada con clave es una restricción para la consulta, algo que nunca debería venir de la URL.
- Una relación `morphTo` puede llevar a cualquier modelo, así que el siguiente tramo vale si existe en alguno de ellos.

### Límites

Lo vimos en [la paginación](#model): ni `limit` ni `per_page` pasan de 1000. Un listado sin límite es la forma más fácil de tumbar una API con una sola petición.

### Filtrar no es autorizar

Los filtros deciden **cómo** se busca, no **qué** puede ver cada usuario. Eso tiene que estar en la consulta antes de filtrar.

Me pasó con las invitaciones. El resource solo enseñaba el email de una invitación a quien la había enviado, pero el listado devolvía las de todos, y con `?email=...` se podían buscar por email. Igual que con los usuarios. La solución fue que cada usuario solo vea las suyas:

```php title="app/Http/Controllers/InvitationController.php"
/**
 * The invitations the user has sent, or every invitation for admins. The resource only shows the email to whoever
 * sent the invitation, and listing the others would let anyone search them by email.
 */
public function index(): JsonResponse
{
    $invitations = Invitation::query()
        ->withGlobalScope('mine', new ViewOnlyMine(allowAdmin: true))
        ->filter(request()->all())
        ->getOrPaginate();

    return $this->indexResponse(InvitationResource::collection($invitations));
}
```

`ViewOnlyMine` es un scope del proyecto que añade `where created_by = <usuario>` salvo para los administradores. Otros modelos, como las notificaciones o los pedidos, lo llevan como scope global, y así ningún filtro puede salirse de lo que es de cada uno.

> [!TIP]
> Una buena pregunta para cada listado: si quito todos los filtros, ¿lo que queda lo puede ver cualquiera que llame a este endpoint? Si la respuesta es no, falta un scope.

## El frontend {#frontend}

Desde el frontend, un listado es un objeto de parámetros. Cada modelo tiene los suyos en un helper:

```js title="resources/js/helpers/TrackHelper.js"
indexParams: {
    with: [
        'album.image',
        'image',
        'audio',
        'createdBy.image',
        'like',
        'favorite',
        'comment',
        'download',
        'product',
        'currentBattle',
        'orderLine'
    ],
    counts: [
        'likes',
        'favorites',
        'downloads',
        'comments',
    ],
    waveform: true
},
```

Y cada página añade lo suyo antes de pedir el listado:

```js title="resources/js/pages/tracks/TrackIndex.vue"
const baseParams = reactive({
    ...TrackHelper.indexParams,
    track_types: [CreationHelper.routeNameToType(route.name)]
});
const { filterForm, inputs } = useFilterForm(trackFilters);
const { items, currentPage, totalPages, loading, makeRequest } = useFetchItems('tracks', baseParams, filterForm);
```

`useFetchItems` junta esos parámetros con los del formulario de filtros y se los pasa a axios en `params`, que convierte las listas en `with[]=album.image&with[]=image...`. Laravel las recibe como arrays. Cuando cambia un filtro, vuelve a pedir el listado.

## Tests {#tests}

Las convenciones comunes se prueban una vez, contra cualquier listado, a través de su endpoint:

```php title="tests/Feature/Filters/FilterTest.php"
#[Test]
public function it_can_filter_by_ranged_values()
{
    Product::query()->update(['price' => 0]);

    create(Product::class, ['price' => 10]);
    create(Product::class, ['price' => 20]);
    create(Product::class, ['price' => 30]);
    create(Product::class, ['price' => 40]);

    $data = $this->makeRequest(route('products.index', [
        'min_price' => 15,
        'max_price' => 32,
    ]));

    $this->assertCount(2, $data);
}

#[Test]
#[TestWith(['where_in'])]
#[TestWith(['boolean'])]
#[TestWith(['nullable'])]
#[TestWith(['filter_multiple'])]
#[TestWith(['apply_relations'])]
#[TestWith(['can_use_column'])]
#[TestWith(['setup'])]
public function an_input_named_after_a_helper_of_the_filter_is_ignored(string $input)
{
    create(User::class, ['name' => 'User 1']);
    create(User::class, ['name' => 'User 2']);

    $data = $this->makeRequest(route('users.index', [$input => 'x']));

    $this->assertCount(2, $data);
}

#[Test]
#[TestWith(['name|asc', ['Ana', 'Bea', 'Carla']])]
#[TestWith(['name|DESC', ['Carla', 'Bea', 'Ana']])]
#[TestWith(['name|sideways', ['Ana', 'Bea', 'Carla']])]
public function results_are_sorted_in_the_given_direction_and_ascending_for_an_unknown_one(string $sort, array $names)
{
    $users = collect(['Bea', 'Carla', 'Ana'])->map(fn (string $name) => create(User::class, ['name' => $name]));

    $data = $this->makeRequest(route('users.index', ['ids' => $users->pluck('id')->all(), 'sort' => $sort]));

    $this->assertSame($names, array_column($data, 'name'));
}
```

`#[TestWith]` repite el mismo test con cada conjunto de datos, y así cada ayudante y cada dirección de orden es un caso distinto en el informe. `create()` es un helper del proyecto: `create(User::class, [...])` equivale a `User::factory()->create([...])`.

Las columnas privadas tienen dos tests. Uno comprueba que nadie más que un administrador puede filtrar u ordenar por ellas:

```php title="tests/Feature/Filters/UserFilterTest.php"
#[Test]
#[TestWith([['email' => 'private']])]
#[TestWith([['not_email' => 'zz-private@mail.com']])]
#[TestWith([['min_birthdate' => '2000-01-01']])]
#[TestWith([['gender' => ['female']]])]
#[TestWith([['roles' => ['manager']]])]
#[TestWith([['sort' => 'email|asc']])]
public function only_admins_can_filter_or_sort_users_by_private_fields(array $input)
{
    $admin = User::factory()->admin()->create();
    User::query()->update(['gender' => Gender::MALE, 'birthdate' => '1980-01-01']);
    User::query()->whereKeyNot($admin->id)->update(['role' => Roles::USER]);
    create(User::class, [
        'email' => 'zz-private@mail.com',
        'birthdate' => '2005-01-01',
        'gender' => Gender::FEMALE,
        'role' => Roles::MANAGER,
        'created_at' => now()->addMinute(),
    ]);
    $everyone = User::filter([])->pluck('id')->all();

    $this->assertSame($everyone, User::filter($input)->pluck('id')->all());

    $this->actingAs($admin);

    $this->assertNotSame($everyone, User::filter($input)->pluck('id')->all());
}
```

El truco está en el usuario que se crea al final: es el único con ese email, esa fecha, ese género y ese rol, y es el más nuevo. Cualquier filtro u orden por un campo privado cambia la lista, así que basta con comparar el resultado con la lista sin filtrar. Para un invitado tiene que ser igual, y para un administrador, distinta.

El otro prueba lo mismo con los nombres en mayúsculas:

```php title="tests/Feature/Filters/UserFilterTest.php"
#[Test]
#[TestWith([['EMAIL' => 'private']])]
#[TestWith([['not_EMAIL' => 'zz-private@mail.com']])]
#[TestWith([['min_BIRTHDATE' => '2000-01-01']])]
#[TestWith([['sort' => 'EMAIL|asc']])]
public function private_fields_cannot_be_used_by_changing_the_case_of_their_name(array $input)
{
    User::query()->update(['birthdate' => '1980-01-01']);
    create(User::class, [
        'email' => 'zz-private@mail.com',
        'birthdate' => '2005-01-01',
        'created_at' => now()->addMinute(),
    ]);
    $everyone = User::filter([])->pluck('id')->all();

    $this->assertSame($everyone, User::filter($input)->pluck('id')->all());
}
```

> [!TIP]
> Un test de seguridad tiene que fallar sin el arreglo. Antes de dar por bueno este, lo pasé sin los cambios en `Filter`, y fallaban los cuatro casos.

## Mejoras posibles {#improvements}

- **Columnas permitidas en lugar de prohibidas.** Con una lista de columnas privadas, una columna sensible nueva queda expuesta hasta que alguien se acuerda de añadirla. Con una lista de columnas permitidas pasa al revés: lo nuevo está cerrado hasta que se abre. Cuesta más mantenerla, pero el error por olvido es el seguro.
- **Ignorar las columnas que no existen.** Hoy, `?sort=nope|asc` llega a la consulta y la base de datos responde con un error, así que la API devuelve un 500. Comparar la columna con `Schema::getColumnListing()` (guardado en caché por tabla) devolvería el listado sin ordenar, como con una dirección desconocida.

## Código {#code}

Los ficheros completos de la clase base y del helper de relaciones. `IsFilterable` está completo en [El modelo y la paginación](#model).

**app/Filters/Filter.php**

```php title="app/Filters/Filter.php"
<?php

namespace App\Filters;

use App\Enums\Roles;
use App\Helpers\RelationHelper;
use App\Interfaces\FilterableMorph;
use App\Interfaces\HasCreatedByInterface;
use Carbon\Carbon;
use EloquentFilter\ModelFilter;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;

class Filter extends ModelFilter
{
    private const MIN_PREFIX = 'min_'; // Prefix used by front to indicate a min value in a range filter

    private const MAX_PREFIX = 'max_'; // Prefix used by front to indicate a max value in a range filter

    private const NOT_PREFIX = 'not_'; // Prefix used by front to indicate excluded values

    private const WITH = 'with'; // Input key to specify eager load relations

    private const COUNT = 'counts'; // Input key to specify counting relations

    private const RANDOMIZE = 'randomize'; // Applies random order

    private const SORT = 'sort'; // Sorting

    private const LIMIT = 'limit'; // Limit results

    /**
     * Columns only admins may filter or sort by, because the API only shows them to their owner and to admins.
     *
     * @var array<int, string>
     */
    protected array $privateColumns = [];

    /**
     * @var array<string, mixed>
     */
    protected $input;

    /**
     * @var array<int, string>
     */
    private array $enumColumns;

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $input
     */
    public function __construct($query, array $input = [], $relationsEnabled = true)
    {
        parent::__construct($query, $input, $relationsEnabled);

        $this->enumColumns = collect($this->query->getModel()->getCasts())
            ->filter(fn (string $cast) => Str::startsWith($cast, 'App\Enums'))
            ->keys()
            ->toArray();
    }

    /**
     * Each input key calls the filter method with its name. Only public methods are filters:
     * the protected and private ones are helpers, and calling them with the input would fail.
     * The key has to spell the method exactly. PHP finds a method whatever its case, but the
     * blacklist compares names exactly, so "EMAIL" would run email() even when it is blacklisted.
     *
     * @param  string  $method
     */
    public function methodIsCallable($method): bool
    {
        if (! parent::methodIsCallable($method)) {
            return false;
        }

        $reflection = new ReflectionMethod($this, $method);

        return $reflection->isPublic() && $reflection->getName() === $method;
    }

    /**
     * Filter methods named after a private column are ignored for everyone but admins.
     */
    protected function setup(): void
    {
        collect($this->privateColumns)
            ->reject(fn (string $column) => $this->canUseColumn($column))
            ->each(fn (string $column) => $this->blacklistMethod(Str::camel($column)));
    }

    /**
     * @return Builder<Model>
     */
    public function handle(): Builder
    {
        $query = parent::handle();

        $this->applyRanges();

        $this->applyNotFilters();

        $this->applyRelations();

        $this->applyConstants();

        $this->applyCounts();

        $this->applySorting();

        $this->applyLimit();

        return $query;
    }

    private function applyRanges(): void
    {
        collect(array_keys($this->input))
            ->filter(fn (string $input) => Str::startsWith($input, [self::MIN_PREFIX, self::MAX_PREFIX]) && ! method_exists($this, Str::camel($input)))
            ->map(fn (string $input) => $this->applyRange($input, $this->input[$input]));
    }

    private function applyNotFilters(): void
    {
        collect(array_keys($this->input))
            ->filter(fn (string $input) => Str::startsWith($input, [self::NOT_PREFIX]) && ! method_exists($this, Str::camel($input)))
            ->map(fn (string $input) => $this->applyNotFilter($input, $this->input[$input]));
    }

    private function applyRelations(): void
    {
        $relations = resolve(RelationHelper::class)->requested($this->query->getModel(), $this->input[self::WITH] ?? null);

        $this->query->with($relations);
    }

    private function applyConstants(): void
    {
        loadConstants()
            ->map(fn (string $constantClass) => Str::snake(class_basename($constantClass)))
            ->filter(fn (string $constantName) => $this->shouldFilterConstant($constantName))
            ->each(function (string $constantName) {
                $value = $this->input[$constantName];
                $column = Str::singular($constantName);
                if (is_array($value)) {
                    $this->query->whereIn($column, $value);
                } else {
                    $this->query->where($column, $value);
                }
            });
    }

    /**
     * A count is a relation of the model ("comments") or a relation of one of its relations ("createdBy.tracks").
     */
    private function applyCounts(): void
    {
        $counts = collect(resolve(RelationHelper::class)->requested($this->query->getModel(), $this->input[self::COUNT] ?? null, maxDepth: 2));

        $rootCounts = $counts
            ->reject(fn (string $path) => str_contains($path, '.'))
            ->values()
            ->all();

        $nestedCounts = $counts
            ->filter(fn (string $path) => str_contains($path, '.'))
            ->mapWithKeys(function (string $path) {
                [$relation, $countedRelation] = explode('.', $path);

                return [$relation => fn ($query) => $query->withCount($countedRelation)];
            })
            ->all();

        if (count($nestedCounts)) {
            $this->query->with($nestedCounts);
        }

        $this->query->withCount($rootCounts);
    }

    private function applyRange(string $input, mixed $value): void
    {
        // Clean up prefixes to determine the table's column name
        $column = str_replace(self::MIN_PREFIX, '', str_replace(self::MAX_PREFIX, '', $input));

        if (! $this->canUseColumn($column)) {
            return;
        }

        $operator = Str::startsWith($input, self::MIN_PREFIX)
            ? '>='
            : '<=';

        if (isDateString($value)) {
            try {
                $date = Carbon::parse($value);
                $dateString = $operator === '>='
                    ? $date->startOfDay()->toDateTimeString()
                    : $date->endOfDay()->toDateTimeString();

                $this->query->where($column, $operator, $dateString);
            } catch (Exception) {
                // A date that cannot be read leaves the range unfiltered
            }
        } else {
            $this->query->where($column, $operator, (float) $value);
        }
    }

    private function applyNotFilter(string $input, mixed $value): void
    {
        // Clean up prefixes to determine the table's column name
        $column = str_replace(self::NOT_PREFIX, '', $input);

        if (! $this->canUseColumn($column)) {
            return;
        }

        if (is_array($value)) {
            $this->whereIn($column, $value, 'and', true); // Where not in
        } else {
            $this->where($column, '!=', $value);
        }
    }

    /**
     * Orders by a 'sort' string if it's present in request
     * This sort string must be with the format 'attribute|orderDirection'
     * for example: a string 'name|asc' means: ordering by 'name' in 'ascending' order.
     * By default, it will order by created_at
     */
    private function applySorting(): void
    {
        $table = $this->query->getModel()->getTable();
        $sortString = $this->input[self::SORT] ?? '';
        [$attribute, $direction] = str_contains($sortString, '|') ? explode('|', $sortString) : [null, null];

        if ($this->input[self::RANDOMIZE] ?? false) {
            $this->query->inRandomOrder();
        } elseif ($attribute && $this->canUseColumn($attribute)) {
            $this->query->getQuery()->orders = []; // clean up any previous ordering

            $this->query->orderBy("$table.$attribute", Str::lower($direction ?? '') === 'desc' ? 'desc' : 'asc');
        } else {
            $this->query->latest("$table.created_at"); // default ordering
        }
    }

    /**
     * Apply limit
     */
    private function applyLimit(): void
    {
        $limit = $this->input[self::LIMIT] ?? null;

        if ($limit) {
            $this->query->limit($limit);
        }
    }

    public function createdBy(mixed $userIds): void
    {
        if ($this->query->getModel() instanceof HasCreatedByInterface) {
            $this->whereIn('created_by', $userIds);
        }
    }

    public function ids(mixed $ids): void
    {
        $this->filterMultiple('id', $ids);
    }

    public function exceptIds(mixed $ids): void
    {
        $this->whereIn('id', $ids, 'and', true);
    }

    public function id(mixed $id): void
    {
        $this->where('id', $id);
    }

    public function modelType(mixed $modelType): void
    {
        $model = $this->query->getModel();

        if (method_exists($model, 'getMorphColumn')) {
            $column = $model->getMorphColumn();
            $this->where("{$column}_type", classByString($modelType));
        }
    }

    public function model(mixed $modelId): void
    {
        $model = $this->query->getModel();

        if ($model instanceof FilterableMorph) {
            $column = $model->getMorphColumn();
            $this->where("{$column}_id", $modelId);
        }
    }

    protected function filterMultiple(string $column, mixed $values): void
    {
        if (is_string($values) && str_contains($values, ',')) {
            $values = explode(',', $values);
        }

        if (! is_array($values)) {
            $values = [$values];
        }

        $this->whereIn($column, $values);
    }

    protected function whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false): void
    {
        if (empty($values)) {
            return;
        }

        if (! is_array($values)) {
            $values = [$values];
        }

        $values = collect($values)
            ->filter() // TODO: filter 'null' strings
            ->toArray();

        $table = $this->query->getModel()->getTable();

        if (count($values)) {
            parent::whereIn("$table.$column", $values, $boolean, $not);
        }
    }

    public function name(mixed $name): void
    {
        collect(Arr::wrap($name))
            ->each(fn ($value) => $this->where('name', 'like', "%$value%"));
    }

    protected function nullable(string $column, mixed $value): void
    {
        $value
            ? $this->whereNotNull($column)
            : $this->whereNull($column);
    }

    /**
     * @param  Builder<Model>|null  $customBuilder
     */
    protected function boolean(string $column, mixed $value, ?Builder $customBuilder = null): void
    {
        $builder = $customBuilder ?? $this;

        $value
            ? $builder->where(fn (Builder $q) => $q->where($column, '!=', 0)->orWhere($column, '!=', false))
            : $builder->where(fn (Builder $q) => $q->where($column, 0)->orWhere($column, false)->orWhereNull($column));
    }

    private function shouldFilterConstant(string $constant): bool
    {
        return ! method_exists($this, $constant)
            && isset($this->input[$constant])
            && in_array(Str::singular($constant), $this->enumColumns)
            && $this->canUseColumn(Str::singular($constant));
    }

    /**
     * Only a column name in snake case may reach the query. MySQL reads "EMAIL" as the column "email",
     * so any other spelling could use a private column under a name that is not on the list.
     */
    private function canUseColumn(string $column): bool
    {
        return preg_match('/^[a-z0-9_]+$/', $column) === 1
            && (! in_array($column, $this->privateColumns, true) || hasRole(Roles::ADMIN));
    }
}
```

**app/Helpers/RelationHelper.php**

```php title="app/Helpers/RelationHelper.php"
<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Turns the relations a request asks for (`with`, `counts`) into paths that Eloquent can load safely.
 * Eloquent loads a relation by calling the model method with its name, and runs the value of a keyed
 * entry as a constraint, so only the names of real relation methods may reach it.
 */
class RelationHelper
{
    /**
     * @var array<int, class-string<Model>>|null
     */
    private ?array $modelClasses = null;

    /**
     * The requested paths ("createdBy", "orderable.createdBy") that are relations from the model.
     * Keys, values that are not text, unknown names and paths deeper than the maximum are dropped.
     *
     * @return array<int, string>
     */
    public function requested(Model $model, mixed $requested, int $maxDepth = PHP_INT_MAX): array
    {
        return collect(Arr::wrap($requested))
            ->filter(fn (mixed $path) => is_string($path))
            ->map(fn (string $path) => array_map(fn (string $name) => Str::camel($name), explode('.', $path)))
            ->filter(fn (array $names) => count($names) <= $maxDepth && $this->isPath($model::class, $names))
            ->map(fn (array $names) => implode('.', $names))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, string>  $names
     */
    private function isPath(string $modelClass, array $names): bool
    {
        $modelClasses = [$modelClass];

        foreach ($names as $name) {
            $modelClasses = $this->relatedClasses($modelClasses, $name);

            if ($modelClasses === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * The models that the relation leads to from any of the given models. A morph-to relation can lead to any model.
     *
     * @param  array<int, class-string<Model>>  $modelClasses
     * @return array<int, class-string<Model>>
     */
    private function relatedClasses(array $modelClasses, string $name): array
    {
        return collect($modelClasses)
            ->filter(fn (string $modelClass) => $this->isRelationMethod($modelClass, $name))
            ->flatMap(function (string $modelClass) use ($name) {
                $relation = (new $modelClass)->{$name}();

                if ($relation instanceof MorphTo) {
                    return $this->modelClasses();
                }

                return $relation instanceof Relation ? [$relation->getRelated()::class] : [];
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Only a public method declared to return a relation counts, so checking a name never calls any other method.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function isRelationMethod(string $modelClass, string $name): bool
    {
        if (! method_exists($modelClass, $name)) {
            return false;
        }

        $method = new ReflectionMethod($modelClass, $name);
        $returnType = $method->getReturnType();

        return $method->isPublic()
            && ! $method->isStatic()
            && $method->getNumberOfRequiredParameters() === 0
            && $returnType instanceof ReflectionNamedType
            && is_a($returnType->getName(), Relation::class, true);
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function modelClasses(): array
    {
        if ($this->modelClasses !== null) {
            return $this->modelClasses;
        }

        $this->modelClasses = [];

        foreach (glob(app_path('Models').'/*.php') ?: [] as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (is_subclass_of($class, Model::class)) {
                $this->modelClasses[] = $class;
            }
        }

        return $this->modelClasses;
    }
}
```

¡Y ya está!

Ahora un listado nuevo es un modelo con `IsFilterable`, un controlador de dos líneas y, si hace falta, un par de métodos en su filtro. El frontend lo pide como todos los demás, y la clase base se encarga de que la URL no pueda pedir más de lo que debe.
