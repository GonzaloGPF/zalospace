---
title: 'SEO para una SPA de Vue servida por Laravel'
description: Cómo dar a cada página de una SPA de Vue su título, descripción, Open Graph y JSON-LD desde Laravel, sin SSR, con 404 de verdad, páginas privadas sin indexar y un sitemap generado desde la base de datos.
---

# SEO para una SPA de Vue servida por Laravel

## Introducción {#intro}

[Talent Battles](https://talentbattles.com) es una SPA hecha con Vue 3 y Vue Router. Laravel sirve una única vista con un `<div id="app">` y, a partir de ahí, Vue pinta todo lo demás.

Para quien usa la web esto funciona muy bien, pero los buscadores y las redes sociales leen otra cosa: el HTML que devuelve el servidor.

- **Las redes sociales no ejecutan JavaScript.** WhatsApp, LinkedIn, Facebook, X o Slack leen el HTML tal cual. Si todas las URLs devuelven el mismo HTML, todos los enlaces se comparten con el mismo título, la misma descripción y la misma imagen.
- **Google sí ejecuta JavaScript, pero más tarde.** Primero lee el HTML y deja el renderizado para después. Si el HTML de todas las páginas es igual, empiezas con mal pie.
- **Una SPA responde 200 a cualquier URL.** `/lo-que-sea` devuelve la misma página que la home, con un 200. Para Google eso es un contenido duplicado (un *soft 404*), y puede acabar indexando copias de tu home.

En este tutorial voy a explicar cómo lo resuelvo sin cambiar nada de Vue: Laravel, que ya está sirviendo la vista, pinta en ella las meta tags de cada página.

Este es el `<head>` que devuelve Laravel para una batalla (con nombres de ejemplo):

```html
<title>Intro Rap vs Noche | Talent Battles</title>
<meta name="description" content="«Intro Rap», de Zalo Beats, contra «Noche», de Luna. Escucha las dos y vota la mejor en Talent Battles.">
<link rel="canonical" href="https://talentbattles.com/battles/288">
<meta property="og:site_name" content="Talent Battles">
<meta property="og:locale" content="es_ES">
<meta property="og:type" content="website">
<meta property="og:url" content="https://talentbattles.com/battles/288">
<meta property="og:title" content="Intro Rap vs Noche | Talent Battles">
<meta property="og:description" content="«Intro Rap», de Zalo Beats, contra «Noche», de Luna. Escucha las dos y vota la mejor en Talent Battles.">
<meta property="og:image" content="https://talentbattles.com/battles/288/share-image?v=3f2a9c81b0d4">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Talent Battles","item":"https://talentbattles.com"},{"@type":"ListItem","position":2,"name":"Batallas","item":"https://talentbattles.com/battles"},{"@type":"ListItem","position":3,"name":"Intro Rap vs Noche","item":"https://talentbattles.com/battles/288"}]}]}</script>
```

> [!NOTE]
> La imagen de `og:image` la dibuja Laravel para cada batalla. Lo explico en el tutorial [Imágenes para compartir enlaces con Laravel](/tutorials/es/share_images).

### El porqué de este tutorial

La respuesta habitual a "mi SPA no posiciona" es el renderizado en el servidor (SSR): [Nuxt](https://nuxt.com/), o [Inertia](https://inertiajs.com/) con SSR. Funcionan, pero añaden un proceso de Node en el servidor, más piezas que desplegar y vigilar, y cambios en el frontend.

En Talent Battles solo hay unos pocos tipos de página pública: la home, las secciones, los tracks, los diseños, las batallas, los álbumes y los perfiles. Para eso basta con:

- Una ruta de Laravel para cada tipo de página pública, igual que la de Vue Router.
- Un objeto con las meta tags de cada página.
- Una vista que las pinta.

> [!TIP]
> El precio de esta solución es tener las rutas públicas dos veces, en Laravel y en Vue Router. Con unos pocos tipos de página compensa. Si tu web tiene cientos de páginas distintas o necesitas que el contenido completo esté en el HTML, entonces sí te conviene el SSR.

## Requisitos {#requirements}

- [Laravel](https://laravel.com/): uso Laravel 13 con PHP 8.4.
- Una SPA de [Vue](https://vuejs.org/) servida por Laravel, con [Vue Router](https://router.vuejs.org/) en modo `createWebHistory()`, es decir, con URLs sin `#`.
- Nada más. No hace falta ningún paquete.

## La idea {#idea}

Todas las páginas de la SPA devuelven la misma vista. Lo único que cambia de una a otra es el objeto `PageMeta` que le pasamos:

| URL | Qué devuelve Laravel |
| :- | :- |
| `/` | La SPA con las meta tags de la home |
| `/beats`, `/songs`, `/battles`... | La SPA con las meta tags de la sección |
| `/beats/1`, `/designs/5`, `/battles/288`... | La SPA con las meta tags de ese track, diseño o batalla |
| `/battles/999999` (no existe) | La SPA con un **404** y `noindex` |
| `/admin/...`, `/profile`... | La SPA con `noindex` |
| Cualquier otra URL | La SPA con un **404** y `noindex` |

La SPA siempre arranca, así que Vue Router se encarga de mostrar la página correcta, o su propia página de "no encontrado". Lo que cambia es lo que leen los robots.

## Las rutas {#routes}

Las rutas de las páginas públicas van en `routes/web.php`, después de todas las demás:

```php title="routes/web.php"
Route::get('/', [WelcomeController::class, 'index'])
    ->name('welcome');

/**
 * Pages of the SPA with their own meta tags. A missing model still gets the SPA, with a 404.
 */
Route::controller(PageController::class)
    ->name('pages.')
    ->missing(fn () => app(PageResponse::class)->render(status: Response::HTTP_NOT_FOUND))
    ->group(function () {
        Route::get('{section}', 'index')
            ->whereIn('section', PageController::SECTIONS)
            ->name('index');

        Route::get('{trackType}/{track}', 'track')
            ->whereIn('trackType', array_map(fn (TrackTypes $type) => $type->getUrlSegment(), TrackTypes::cases()))
            ->whereNumber('track')
            ->name('tracks.show');

        Route::get('designs/{design}', 'design')
            ->whereNumber('design')
            ->name('designs.show');

        Route::get('battles/{battle}', 'battle')
            ->whereNumber('battle')
            ->name('battles.show');

        Route::get('albums/{album}', 'album')
            ->whereNumber('album')
            ->name('albums.show');

        Route::get('users/{user}', 'user')
            ->whereNumber('user')
            ->name('users.show');

        Route::get('{section}/{path?}', 'private')
            ->whereIn('section', PageController::PRIVATE_SECTIONS)
            ->where('path', '.*')
            ->name('private');
    });

// Catch-all route. It has to be a fallback: LaRecipe registers its routes after this file is loaded.
Route::fallback([WelcomeController::class, 'notFound']);
```

Hay varios detalles importantes:

- **`whereIn()` y `whereNumber()`.** Una ruta como `{section}` atraparía cualquier URL de un solo segmento: `/login`, `/sitemap.xml`, `/docs`... Con `whereIn()` solo acepta las secciones que existen, y con `whereNumber()` los ids tienen que ser números.
- **`missing()`.** Si el modelo no existe (`/battles/999999`), el *route model binding* lanzaría un 404 con la página de error de Laravel. Con `missing()` devolvemos la SPA, pero con un 404, para que Vue muestre su propia página de "no encontrado" y Google sepa que esa URL no existe.
- **Las páginas privadas.** El panel de administración, el perfil o las estadísticas también son páginas de la SPA, pero no tienen nada que indexar. Las agrupo en una ruta que acepta cualquier subruta (`where('path', '.*')`).
- **El *fallback*.** Cualquier URL que no tenga ruta devuelve la SPA con un 404.

> [!WARNING]
> La tentación es escribir una ruta comodín como `Route::get('{any}', ...)->where('any', '.*')`. No lo hagas: atraparía también las rutas que registran los paquetes después de cargar `routes/web.php`.
> A mí me pasaba con [LaRecipe](https://larecipe.saleem.dev/), que sirve la documentación en `/docs`. `Route::fallback()` siempre se evalúa la última, así que no le quita las rutas a nadie.

## Siempre la misma vista {#response}

El controlador es muy fino: cada método le pide a un servicio las meta tags de la página y se las pasa a la vista.

```php title="app/Http/Controllers/PageController.php"
/**
 * Pages of the SPA. They all render the same view, each one with its own meta tags.
 */
class PageController extends Controller
{
    public const SECTIONS = ['beats', 'songs', 'samples', 'designs', 'battles', 'users', 'contributors'];

    public const PRIVATE_SECTIONS = ['admin', 'carts', 'commerce', 'password', 'profile', 'stats'];

    public function __construct(private PageResponse $pageResponse, private PageMetaService $pageMetaService)
    {
        parent::__construct();
    }

    public function index(string $section): Response
    {
        return $this->pageResponse->render($this->pageMetaService->section($section));
    }

    /**
     * A track opened under the URL of another type, like a song in /beats/1, goes to its own URL.
     */
    public function track(string $trackType, Track $track): Response|RedirectResponse
    {
        if ($trackType !== $track->track_type->getUrlSegment()) {
            return redirect()->to($this->pageMetaService->trackUrl($track), Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->pageResponse->render($this->pageMetaService->track($track));
    }

    public function battle(Battle $battle): Response
    {
        return $this->pageResponse->render($this->pageMetaService->battle($battle));
    }

    // design(), album() and user() work the same way

    public function private(): Response
    {
        return $this->pageResponse->render();
    }
}
```

Fíjate en `track()`. Un track puede ser un beat, una canción o un sample, y cada tipo vive en su sección: `/beats/1`, `/songs/2`... Si alguien abre una canción con la URL de un beat (`/beats/2`), no le devolvemos la página: le **redirigimos con un 301** a su URL buena.
Así cada track tiene una única URL y Google no ve dos páginas con el mismo contenido.

La respuesta la construye una clase pequeña, `PageResponse`, que usan todos los controladores de páginas:

```php title="app/Responses/PageResponse.php"
class PageResponse
{
    public function __construct(private UserService $userService) {}

    /**
     * Renders the SPA. A page without meta tags is not indexed by search engines.
     */
    public function render(?PageMeta $meta = null, int $status = Response::HTTP_OK): Response
    {
        return response()->view('app', [
            'user' => $this->userService->getUserData(),
            'meta' => $meta,
        ], $status);
    }
}
```

Y la home y el *fallback* van en su propio controlador:

```php title="app/Http/Controllers/WelcomeController.php"
public function index(): Response
{
    return $this->pageResponse->render($this->pageMetaService->home());
}

/**
 * Any path without a route. The SPA still answers it, but with a 404 so that search engines
 * do not index it as a copy of the home page.
 */
public function notFound(): Response|JsonResponse
{
    if (request()->wantsJson()) {
        return $this->simpleResponse->jsonData(trans('exceptions.not_found'), [
            'path' => request()->url(),
        ], Response::HTTP_NOT_FOUND);
    }

    return $this->pageResponse->render(status: Response::HTTP_NOT_FOUND);
}
```

> [!NOTE]
> Si la petición espera JSON (por ejemplo, una llamada de axios a una ruta de la API que no existe), devolvemos un 404 en JSON y no el HTML de la SPA.

## PageMeta: lo que leen los robots {#page_meta}

`PageMeta` es un objeto sencillo con todo lo que necesita la vista:

```php title="app/Objects/PageMeta.php"
/**
 * Meta tags of a page of the SPA: what search engines and link previews read, since they do not
 * run its JavaScript.
 */
class PageMeta
{
    /**
     * @param  array<int, array<string, mixed>>  $structuredData  schema.org objects, rendered as JSON-LD
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public string $image,
        public string $imageType,
        public string $locale,
        public string $type = 'website',
        public bool $indexable = true,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
        public array $structuredData = [],
    ) {}

    /**
     * JSON-LD for a script tag. Escaping < and > keeps a name like "</script>" from closing it.
     */
    public function structuredDataJson(): string
    {
        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $this->structuredData],
            JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
```

> [!WARNING]
> El JSON-LD va dentro de un `<script>`, y ahí Blade no puede escapar nada. Si un usuario llama a su beat `</script><script>alert(1)</script>`, ese nombre cerraría la etiqueta y ejecutaría su código.
> `JSON_HEX_TAG` convierte `<` y `>` en `<` y `>`: el JSON sigue siendo válido y el navegador ya no ve ninguna etiqueta.

## La vista {#view}

La vista de la SPA es la de siempre. Lo único nuevo es que el layout incluye un *partial* con las meta tags:

```blade title="resources/views/layouts/base.blade.php"
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- ... --}}
    @include('partials.meta', ['meta' => $meta ?? null])
    {{-- ... --}}
</head>
```

```blade title="resources/views/partials/meta.blade.php"
@if($meta)
    <title>{{ $meta->title }}</title>
    <meta name="description" content="{{ $meta->description }}">
    <link rel="canonical" href="{{ $meta->url }}">
    @unless($meta->indexable)
        <meta name="robots" content="noindex">
    @endunless
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ $meta->locale }}">
    <meta property="og:type" content="{{ $meta->type }}">
    <meta property="og:url" content="{{ $meta->url }}">
    <meta property="og:title" content="{{ $meta->title }}">
    <meta property="og:description" content="{{ $meta->description }}">
    <meta property="og:image" content="{{ $meta->image }}">
    <meta property="og:image:type" content="{{ $meta->imageType }}">
    @if($meta->imageWidth && $meta->imageHeight)
        <meta property="og:image:width" content="{{ $meta->imageWidth }}">
        <meta property="og:image:height" content="{{ $meta->imageHeight }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    @if($meta->structuredData)
        <script type="application/ld+json">{!! $meta->structuredDataJson() !!}</script>
    @endif
@else
    <title>{{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
@endif
```

La regla es sencilla: **una página sin `PageMeta` no se indexa.** Así, las páginas privadas, los 404 y cualquier página nueva que se nos olvide preparar quedan fuera de Google por defecto.

> [!TIP]
> Todos los valores van con `{{ }}`, que escapa el HTML. Solo el JSON-LD usa `{!! !!}`, porque ya viene escapado por `JSON_HEX_TAG`.

## PageMetaService: el contenido de cada página {#service}

Toda la lógica de qué título, descripción e imagen tiene cada página está en un servicio. Los métodos públicos son uno por tipo de página, y todos terminan llamando a `page()`.

### La home

La home lleva además el JSON-LD que describe la web y la organización que hay detrás:

```php title="app/Services/PageMetaService.php"
public function home(): PageMeta
{
    $home = route('welcome');

    return $this->page(
        title: trans('seo.home_title', ['app' => config('app.name')]),
        description: trans('seo.description'),
        url: $home,
        structuredData: [
            [
                '@type' => 'WebSite',
                '@id' => "$home#website",
                'url' => $home,
                'name' => config('app.name'),
                'publisher' => ['@id' => "$home#organization"],
            ],
            [
                '@type' => 'Organization',
                '@id' => "$home#organization",
                'url' => $home,
                'name' => config('company.name'),
                'logo' => asset(self::LOGO),
            ],
        ],
    );
}
```

### Un track

```php title="app/Services/PageMetaService.php"
public function track(Track $track): PageMeta
{
    $track->loadMissing(['image', 'album.image', 'createdBy.image']);

    $replace = $this->creationReplacements($track->name, $track->createdBy) + [
        'type' => trans("seo.track_types.{$track->track_type->value}"),
    ];
    $section = $track->track_type->getUrlSegment();
    $url = $this->trackUrl($track);

    return $this->page(
        title: $this->title(trans('seo.track_title', $replace)),
        description: $this->description($track->description, trans('seo.track_description', $replace)),
        url: $url,
        image: $track->image ?? $track->album->image ?? $track->createdBy?->image,
        structuredData: [$this->breadcrumbs([
            [trans("seo.pages.$section.title"), route('pages.index', ['section' => $section])],
            [$track->name, $url],
        ])],
    );
}
```

- **El título** sale de una traducción: `':name, :type de :author'` y luego `':page | :app'`. Por ejemplo: *Intro Rap, beat de Zalo Beats | Talent Battles*.
- **La imagen** es la del track, si no la de su álbum y, si no, la foto de su autor.
- **La descripción** es la del track, pero esa descripción viene de un editor de texto enriquecido. Hay que limpiarla.

### La descripción

```php title="app/Services/PageMetaService.php"
private const DESCRIPTION_LENGTH = 155;

/**
 * Descriptions come from a rich text editor. A space goes before each tag so that paragraphs
 * do not run into each other once the tags are removed.
 */
private function description(?string $text, string $fallback): string
{
    $plainText = Str::of(html_entity_decode(strip_tags(str_replace('<', ' <', (string) $text)), ENT_QUOTES | ENT_HTML5))
        ->squish();

    return $plainText->isEmpty()
        ? $fallback
        : $plainText->limit(self::DESCRIPTION_LENGTH)->toString();
}
```

Paso a paso, con `<p>Un beat oscuro.</p><p>Para rap &amp; trap.</p>`:

1. `str_replace('<', ' <', ...)` mete un espacio antes de cada etiqueta. Sin él, al quitar las etiquetas quedaría *"Un beat oscuro.Para rap"*.
2. `strip_tags()` quita las etiquetas.
3. `html_entity_decode()` convierte `&amp;` en `&`. Si no, Blade lo volvería a escapar y en Google se vería `&amp;amp;`.
4. `squish()` deja un solo espacio entre palabras y quita los del principio y el final.
5. `limit(155)` la corta a la longitud que Google suele mostrar en los resultados.

El resultado es *"Un beat oscuro. Para rap & trap."*. Si la descripción está vacía, usamos una genérica: *"Escucha «Intro Rap», beat de Zalo Beats en Talent Battles."*.

### Un perfil

```php title="app/Services/PageMetaService.php"
/**
 * Profiles without creations are thin content, so search engines are asked not to index them.
 */
public function user(User $user): PageMeta
{
    // ...

    return $this->page(
        title: $this->title($name),
        description: $description,
        url: $url,
        image: $user->image,
        type: 'profile',
        indexable: $user->tracks()->exists() || $user->designs()->exists(),
        structuredData: [
            [
                '@type' => 'ProfilePage',
                // ...
            ],
            $this->breadcrumbs([
                [trans('seo.pages.users.title'), route('pages.index', ['section' => 'users'])],
                [$name, $url],
            ]),
        ],
    );
}
```

Un perfil sin creaciones es una página casi vacía: Google lo llama *thin content* y no le gusta encontrarse muchas. Por eso esos perfiles llevan `noindex` (y, como veremos, tampoco van al sitemap).

### Las migas de pan

Cada página lleva un `BreadcrumbList` en su JSON-LD. Google lo usa para mostrar la ruta de la página en los resultados (*Talent Battles › Beats › Intro Rap*) en lugar de la URL.

```php title="app/Services/PageMetaService.php"
/**
 * Every trail starts at the home page. Pages are pairs and not name => URL, because two pages
 * can have the same name, and a name like "2025" would become an integer key.
 *
 * @param  array<int, array{string, string}>  $pages  name and URL of each page after the home page
 * @return array<string, mixed>
 */
private function breadcrumbs(array $pages): array
{
    $trail = [[config('app.name'), route('welcome')], ...$pages];

    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(fn (array $page, int $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $page[0],
            'item' => $page[1],
        ], $trail, array_keys($trail)),
    ];
}
```

> [!WARNING]
> Lo natural sería pasar las páginas como `['Beats' => $url, 'Intro Rap' => $url]`, pero eso falla en dos casos:
> si alguien llama a su beat "Beats", las dos claves son iguales y se pierde una miga; y si lo llama "2025", PHP convierte la clave en el entero `2025`.
> Por eso cada página es un par `[nombre, url]`.

### page()

Todos los métodos terminan aquí:

```php title="app/Services/PageMetaService.php"
/**
 * A page shares its own image, a JPEG of the default size drawn for it, or the default image.
 *
 * @param  array<int, array<string, mixed>>  $structuredData
 */
private function page(
    string $title,
    string $description,
    string $url,
    ?File $image = null,
    ?string $drawnImageUrl = null,
    string $type = 'website',
    bool $indexable = true,
    array $structuredData = [],
): PageMeta {
    return new PageMeta(
        title: $title,
        description: $description,
        url: $url,
        image: $drawnImageUrl ?? $this->imageUrl($image),
        imageType: $image ? $image->mime : self::DEFAULT_IMAGE_TYPE,
        locale: app()->getLocale() === 'es' ? 'es_ES' : 'en_US',
        type: $type,
        indexable: $indexable,
        imageWidth: $image ? null : self::DEFAULT_IMAGE_WIDTH,
        imageHeight: $image ? null : self::DEFAULT_IMAGE_HEIGHT,
        structuredData: $structuredData,
    );
}
```

La imagen puede ser de tres tipos: una dibujada para la página (las batallas), un fichero subido por el usuario o la imagen por defecto de la web.
Solo indicamos el tamaño cuando lo conocemos, es decir, cuando la imagen la hemos hecho nosotros a 1200×630.

> [!NOTE]
> Las meta tags salen en el idioma del visitante. Un middleware elige el idioma con la cabecera `Accept-Language` (`$request->getPreferredLanguage(['es', 'en'])`), y `og:locale` dice cuál es.

## El título al navegar dentro de la SPA {#client_title}

Las meta tags del servidor solo se pintan al cargar la primera página. Cuando navegas con Vue Router, el HTML no se vuelve a pedir.

¿Hay que actualizar las meta tags desde Vue? **No**: los robots siempre cargan cada URL desde cero, así que siempre ven las del servidor. Lo único que sí ve una persona al navegar es el título de la pestaña.

Para el título uso un *guard* `afterEach` de Vue Router, con las **mismas traducciones** que el servidor. [laravel-vue-i18n](https://github.com/xiCO2k/laravel-vue-i18n) carga en Vue los ficheros de `lang/` de Laravel, así que `seo.track_title` es la misma cadena en los dos lados:

```js title="resources/js/guards/after/documentTitle.js"
/**
 * Title of the browser tab, built with the same translations as the meta tags of the server
 * (PageMetaService). The first page keeps the title that the server rendered.
 */
export default (to, from, failure) => {
    if (failure || from === START_LOCATION) {
        return;
    }

    const app = global.getAppName();
    const page = getPageTitle(to);

    if (page) {
        document.title = Translator.t('seo.title', { page, app });
    } else {
        document.title = to.name === 'welcome' ? Translator.t('seo.home_title', { app }) : app;
    }
}

const getPageTitle = (to) => {
    switch (to.name) {
        case 'beats.show':
        case 'songs.show':
        case 'samples.show': {
            const { track } = useTrack();

            return Translator.t('seo.track_title', {
                name: track.name,
                type: Translator.t(`seo.track_types.${ track.track_type }`),
                author: getAuthor(track),
            });
        }

        // the sections, designs, albums, battles and users work the same way
    }
}
```

```js title="resources/js/plugins/router.js"
router.afterEach(documentTitle);
```

> [!NOTE]
> `from === START_LOCATION` es la primera navegación, la que hace Vue Router al arrancar. En ese momento no tocamos el título, porque ya lo ha puesto el servidor.

## El sitemap {#sitemap}

El sitemap le dice a Google qué páginas existen y cuándo cambiaron por última vez. Lo genero desde la base de datos:

```php title="app/Services/SitemapService.php"
class SitemapService
{
    public function __construct(private PageMetaService $pageMetaService) {}

    /**
     * The pages that search engines may index, so users without creations are left out. The
     * pages of the docs are in the sitemap of LaRecipe, /docs/sitemap.xml.
     *
     * @return LazyCollection<int, array{url: string, updated_at: DateTimeInterface|null}>
     */
    public function entries(): LazyCollection
    {
        return LazyCollection::make(function () {
            yield $this->entry(route('welcome'));

            foreach (PageController::SECTIONS as $section) {
                yield $this->entry(route('pages.index', ['section' => $section]));
            }

            foreach (Track::query()->select(['id', 'track_type', 'updated_at'])->lazyById() as $track) {
                yield $this->entry($this->pageMetaService->trackUrl($track), $track->updated_at);
            }

            foreach (Design::query()->select(['id', 'updated_at'])->lazyById() as $design) {
                yield $this->entry(route('pages.designs.show', $design), $design->updated_at);
            }

            foreach (Battle::query()->select(['id', 'updated_at'])->lazyById() as $battle) {
                yield $this->entry(route('pages.battles.show', $battle), $battle->updated_at);
            }

            foreach (Album::query()->select(['id', 'updated_at'])->lazyById() as $album) {
                yield $this->entry(route('pages.albums.show', $album), $album->updated_at);
            }

            $creators = User::query()
                ->select(['id', 'updated_at'])
                ->where(fn (Builder $query) => $query->has('tracks')->orHas('designs'));

            foreach ($creators->lazyById() as $creator) {
                yield $this->entry(route('pages.users.show', $creator), $creator->updated_at);
            }
        });
    }

    /**
     * @return array{url: string, updated_at: DateTimeInterface|null}
     */
    private function entry(string $url, ?DateTimeInterface $updatedAt = null): array
    {
        return ['url' => $url, 'updated_at' => $updatedAt];
    }
}
```

- **`LazyCollection` con `yield`.** Las entradas se generan a medida que la vista las recorre.
- **`lazyById()`.** Lee los modelos de 1000 en 1000, ordenados por id, así que no carga toda la tabla en memoria.
- **`select()`.** Solo pedimos las columnas que necesita la URL y la fecha.
- **Los perfiles sin creaciones no entran**, igual que no se indexan.

La vista es muy sencilla:

```blade title="resources/views/sitemap.blade.php"
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($entries as $entry)
    <url>
        <loc>{{ $entry['url'] }}</loc>
@if($entry['updated_at'])
        <lastmod>{{ $entry['updated_at']->format(DATE_ATOM) }}</lastmod>
@endif
    </url>
@endforeach
</urlset>
```

Pero falta algo: la declaración `<?xml version="1.0" encoding="UTF-8"?>`. No está en la vista a propósito:

```php title="app/Http/Controllers/SitemapController.php"
class SitemapController extends Controller
{
    /**
     * The XML declaration is not in the view. Blade splits templates with the PHP tokenizer, and
     * with short_open_tag on, as in production, it reads "<?xml" as PHP and stops compiling.
     */
    public function __invoke(SitemapService $sitemapService): Response
    {
        $urlset = view('sitemap', ['entries' => $sitemapService->entries()])->render();

        return response('<?xml version="1.0" encoding="UTF-8"?>'."\n".$urlset)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
```

> [!WARNING]
> Si tu servidor tiene activada la opción `short_open_tag` de PHP, `<?` también abre código PHP. Blade lee `<?xml` como el principio de un bloque de PHP y la vista deja de compilar.
> En local puede que no lo veas, porque está desactivada por defecto, pero en el servidor de Talent Battles está activada. La solución es añadir la declaración desde el controlador.

Por último, el `robots.txt` le dice a los buscadores dónde están los sitemaps. La documentación de LaRecipe tiene el suyo:

```text title="public/robots.txt"
User-agent: *
Disallow:

Sitemap: https://talentbattles.com/sitemap.xml
Sitemap: https://talentbattles.com/docs/sitemap.xml
```

## Tests {#tests}

El SEO es muy fácil de romper sin darse cuenta: basta con que alguien añada una ruta o cambie una traducción. Por eso tiene tests de todo lo que hemos visto.

Que cada página tiene su título, su descripción y su URL canónica:

```php title="tests/Feature/Pages/SpaPagesTest.php"
#[Test]
public function track_page_renders_its_name_type_author_and_plain_text_description()
{
    $track = $this->createTrack(['description' => '<p>Un beat oscuro.</p><p>Para rap &amp; trap.</p>']);

    $this->get("/beats/$track->id")
        ->assertOk()
        ->assertSee('<title>Intro Rap, beat de Zalo Beats | Talent Battles</title>', false)
        ->assertSee('<meta name="description" content="Un beat oscuro. Para rap &amp; trap.">', false)
        ->assertSee("<link rel=\"canonical\" href=\"http://talentbattles.test/beats/$track->id\">", false);
}
```

Que una URL con el tipo equivocado redirige con un 301:

```php title="tests/Feature/Pages/SpaPagesTest.php"
#[Test]
public function track_under_the_url_of_another_type_redirects_permanently_to_its_own_url()
{
    $song = $this->createTrack(['track_type' => TrackTypes::SONG]);

    $this->get("/beats/$song->id")
        ->assertMovedPermanently()
        ->assertRedirect("http://talentbattles.test/songs/$song->id");
}
```

Que lo que no existe devuelve un 404 de verdad, y que no se indexa. Con un *data provider* pruebo todos los tipos de página de golpe:

```php title="tests/Feature/Pages/SpaPagesTest.php"
public static function missingModels(): array
{
    return [
        'track' => ['/beats/999999'],
        'design' => ['/designs/999999'],
        'album' => ['/albums/999999'],
        'user' => ['/users/999999'],
        'battle' => ['/battles/999999'],
    ];
}

#[Test]
#[DataProvider('missingModels')]
public function missing_models_render_the_spa_with_a_404_and_noindex(string $url)
{
    $this->get($url)
        ->assertNotFound()
        ->assertSee('id="app"', false)
        ->assertSee('<meta name="robots" content="noindex">', false);
}

#[Test]
public function unknown_paths_are_not_indexed()
{
    $this->get('/some/frontend/route')
        ->assertNotFound()
        ->assertSee('<meta name="robots" content="noindex">', false);
}
```

Que los nombres que escriben los usuarios no pueden inyectar HTML, ni en las meta tags ni en el JSON-LD:

```php title="tests/Feature/Pages/SpaPagesTest.php"
#[Test]
public function names_written_by_users_are_escaped_in_the_meta_tags()
{
    $track = $this->createTrack(['name' => '"><script>alert(1)</script>']);

    // NameFormatter capitalizes each word of the name
    $this->get("/beats/$track->id")
        ->assertOk()
        ->assertDontSee('"><Script>Alert(1)</Script>', false)
        ->assertSee('<meta property="og:title" content="&quot;&gt;&lt;Script&gt;Alert(1)&lt;/Script&gt;, beat de Zalo Beats | Talent Battles">', false);
}

#[Test]
public function names_written_by_users_cannot_close_the_structured_data_script()
{
    $track = $this->createTrack(['name' => '</script><script>alert(1)</script>']);

    $response = $this->get("/beats/$track->id");

    // NameFormatter capitalizes each word of the name
    $response->assertDontSee('</Script><Script>Alert(1)', false);
    $this->assertSame('</Script><Script>Alert(1)</Script>', $this->trail($this->structuredData($response)[0])[2][1]);
}
```

> [!NOTE]
> El segundo test lee el JSON-LD de la respuesta y comprueba que el nombre sigue ahí, entero. No basta con que no aparezca la etiqueta: también tiene que llegar bien a Google.

Y que el sitemap se sirve como XML y empieza por la declaración:

```php title="tests/Feature/Pages/SitemapTest.php"
#[Test]
public function sitemap_is_served_as_xml()
{
    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $this->assertStringStartsWith("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset", $response->getContent());
}
```

## Comprobarlo de verdad {#check}

La forma más rápida es pedir la página como lo haría un robot, sin JavaScript:

```bash
curl -s https://tu-web.com/beats/1 | grep -E "<title>|og:|canonical|robots"
```

Y para el código de estado de una página que no existe:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://tu-web.com/beats/999999
```

Después, las herramientas de Google:

- [Prueba de resultados enriquecidos](https://search.google.com/test/rich-results): comprueba que Google entiende el JSON-LD, por ejemplo las migas de pan.
- [Search Console](https://search.google.com/search-console): envía el sitemap y usa la **inspección de URLs** para ver la página tal y como la ve Google.
- [Validador de schema.org](https://validator.schema.org/): valida cualquier JSON-LD, aunque Google no lo use para resultados enriquecidos.

> [!TIP]
> En Search Console, revisa de vez en cuando el informe de **páginas no indexadas**. Si aparecen URLs que deberían ser 404 marcadas como *soft 404*, falta alguna ruta o algún `noindex`.

## Código {#code}

Las rutas completas están en la sección [Las rutas](#routes), y el controlador en [Siempre la misma vista](#response): sus métodos `design()`, `album()` y `user()` son iguales que `battle()`. Aquí va el servicio entero.

**app/Services/PageMetaService.php**

```php title="app/Services/PageMetaService.php"
<?php

namespace App\Services;

use App\Enums\BattleStates;
use App\Models\Album;
use App\Models\Battle;
use App\Models\Design;
use App\Models\File;
use App\Models\Track;
use App\Models\User;
use App\Objects\PageMeta;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PageMetaService
{
    private const DESCRIPTION_LENGTH = 155;

    /**
     * A JPEG of 1200×630: the format and size every social network shows when a link is shared.
     */
    private const DEFAULT_IMAGE = 'images/share.jpg';

    private const DEFAULT_IMAGE_TYPE = 'image/jpeg';

    private const DEFAULT_IMAGE_WIDTH = 1200;

    private const DEFAULT_IMAGE_HEIGHT = 630;

    private const LOGO = 'images/logo_black_on_white.webp';

    public function __construct(private readonly BattleShareImageService $battleShareImageService) {}

    public function home(): PageMeta
    {
        $home = route('welcome');

        return $this->page(
            title: trans('seo.home_title', ['app' => config('app.name')]),
            description: trans('seo.description'),
            url: $home,
            structuredData: [
                [
                    '@type' => 'WebSite',
                    '@id' => "$home#website",
                    'url' => $home,
                    'name' => config('app.name'),
                    'publisher' => ['@id' => "$home#organization"],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => "$home#organization",
                    'url' => $home,
                    'name' => config('company.name'),
                    'logo' => asset(self::LOGO),
                ],
            ],
        );
    }

    public function section(string $section): PageMeta
    {
        $name = trans("seo.pages.$section.title");
        $url = route('pages.index', ['section' => $section]);

        return $this->page(
            title: $this->title($name),
            description: trans("seo.pages.$section.description"),
            url: $url,
            structuredData: [$this->breadcrumbs([[$name, $url]])],
        );
    }

    public function track(Track $track): PageMeta
    {
        $track->loadMissing(['image', 'album.image', 'createdBy.image']);

        $replace = $this->creationReplacements($track->name, $track->createdBy) + [
            'type' => trans("seo.track_types.{$track->track_type->value}"),
        ];
        $section = $track->track_type->getUrlSegment();
        $url = $this->trackUrl($track);

        return $this->page(
            title: $this->title(trans('seo.track_title', $replace)),
            description: $this->description($track->description, trans('seo.track_description', $replace)),
            url: $url,
            image: $track->image ?? $track->album->image ?? $track->createdBy?->image,
            structuredData: [$this->breadcrumbs([
                [trans("seo.pages.$section.title"), route('pages.index', ['section' => $section])],
                [$track->name, $url],
            ])],
        );
    }

    public function design(Design $design): PageMeta
    {
        $design->loadMissing(['image', 'album.image', 'createdBy.image']);

        $replace = $this->creationReplacements($design->name, $design->createdBy);
        $url = route('pages.designs.show', $design);

        return $this->page(
            title: $this->title(trans('seo.design_title', $replace)),
            description: $this->description($design->description, trans('seo.design_description', $replace)),
            url: $url,
            image: $design->image ?? $design->album->image ?? $design->createdBy?->image,
            structuredData: [$this->breadcrumbs([
                [trans('seo.pages.designs.title'), route('pages.index', ['section' => 'designs'])],
                [$design->name, $url],
            ])],
        );
    }

    /**
     * A battle is named after its two creations. Its description invites to vote while it is open, and gives the
     * result once it has finished. Its image shows the two creations face to face.
     */
    public function battle(Battle $battle): PageMeta
    {
        $battle->loadMissing(['tracks.createdBy', 'designs.createdBy', 'winner']);

        $creations = $battle->getCreations()->values();
        $name = $this->battleName($creations);
        $url = route('pages.battles.show', $battle);

        return $this->page(
            title: $this->title($name),
            description: $this->battleDescription($battle, $creations),
            url: $url,
            drawnImageUrl: $this->battleShareImageService->url($battle),
            structuredData: [$this->breadcrumbs([
                [trans('seo.pages.battles.title'), route('pages.index', ['section' => 'battles'])],
                [$name, $url],
            ])],
        );
    }

    /**
     * There is no list of albums, so the breadcrumbs of an album go through its author.
     */
    public function album(Album $album): PageMeta
    {
        $album->loadMissing(['image', 'createdBy.image']);

        $replace = $this->creationReplacements($album->name, $album->createdBy);
        $url = route('pages.albums.show', $album);
        $author = $album->createdBy ? [[$album->createdBy->getNickName(), route('pages.users.show', $album->createdBy)]] : [];

        return $this->page(
            title: $this->title(trans('seo.album_title', $replace)),
            description: $this->description($album->description, trans('seo.album_description', $replace)),
            url: $url,
            image: $album->image ?? $album->createdBy?->image,
            structuredData: [$this->breadcrumbs([
                [trans('seo.pages.users.title'), route('pages.index', ['section' => 'users'])],
                ...$author,
                [$album->name, $url],
            ])],
        );
    }

    /**
     * Profiles without creations are thin content, so search engines are asked not to index them.
     */
    public function user(User $user): PageMeta
    {
        $user->loadMissing('image');

        $name = $user->getNickName();
        $url = route('pages.users.show', $user);
        $description = $this->description(
            $user->bio ?: $user->quote,
            trans('seo.user_description', ['name' => $name, 'app' => config('app.name')])
        );
        $image = $this->imageUrl($user->image);

        return $this->page(
            title: $this->title($name),
            description: $description,
            url: $url,
            image: $user->image,
            type: 'profile',
            indexable: $user->tracks()->exists() || $user->designs()->exists(),
            structuredData: [
                [
                    '@type' => 'ProfilePage',
                    'url' => $url,
                    'dateCreated' => $user->created_at?->toAtomString(),
                    'dateModified' => $user->updated_at?->toAtomString(),
                    'mainEntity' => [
                        '@type' => 'Person',
                        'identifier' => (string) $user->id,
                        'name' => $name,
                        'description' => $description,
                        'image' => $image,
                    ],
                ],
                $this->breadcrumbs([
                    [trans('seo.pages.users.title'), route('pages.index', ['section' => 'users'])],
                    [$name, $url],
                ]),
            ],
        );
    }

    public function trackUrl(Track $track): string
    {
        return route('pages.tracks.show', [
            'trackType' => $track->track_type->getUrlSegment(),
            'track' => $track,
        ]);
    }

    /**
     * A page shares its own image, a JPEG of the default size drawn for it, or the default image.
     *
     * @param  array<int, array<string, mixed>>  $structuredData
     */
    private function page(
        string $title,
        string $description,
        string $url,
        ?File $image = null,
        ?string $drawnImageUrl = null,
        string $type = 'website',
        bool $indexable = true,
        array $structuredData = [],
    ): PageMeta {
        return new PageMeta(
            title: $title,
            description: $description,
            url: $url,
            image: $drawnImageUrl ?? $this->imageUrl($image),
            imageType: $image ? $image->mime : self::DEFAULT_IMAGE_TYPE,
            locale: app()->getLocale() === 'es' ? 'es_ES' : 'en_US',
            type: $type,
            indexable: $indexable,
            imageWidth: $image ? null : self::DEFAULT_IMAGE_WIDTH,
            imageHeight: $image ? null : self::DEFAULT_IMAGE_HEIGHT,
            structuredData: $structuredData,
        );
    }

    private function imageUrl(?File $image): string
    {
        return $image ? route('web.files.show', $image) : asset(self::DEFAULT_IMAGE);
    }

    private function title(string $page): string
    {
        return trans('seo.title', ['page' => $page, 'app' => config('app.name')]);
    }

    /**
     * Every trail starts at the home page. Pages are pairs and not name => URL, because two pages
     * can have the same name, and a name like "2025" would become an integer key.
     *
     * @param  array<int, array{string, string}>  $pages  name and URL of each page after the home page
     * @return array<string, mixed>
     */
    private function breadcrumbs(array $pages): array
    {
        $trail = [[config('app.name'), route('welcome')], ...$pages];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $page, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $page[0],
                'item' => $page[1],
            ], $trail, array_keys($trail)),
        ];
    }

    /**
     * @param  Collection<int, Track>|Collection<int, Design>  $creations
     */
    private function battleName(Collection $creations): string
    {
        if ($creations->count() >= 2) {
            return trans('seo.battle_title', $this->versusReplacements($creations[0], $creations[1]));
        }

        $first = $creations->first();

        return $first
            ? trans('seo.open_battle_title', $this->creationReplacements($first->name, $first->createdBy))
            : trans('seo.pages.battles.title');
    }

    /**
     * @param  Collection<int, Track>|Collection<int, Design>  $creations
     */
    private function battleDescription(Battle $battle, Collection $creations): string
    {
        if ($creations->count() < 2) {
            $first = $creations->first();

            return $first
                ? trans('seo.open_battle_description', $this->creationReplacements($first->name, $first->createdBy))
                : trans('seo.pages.battles.description');
        }

        if ($battle->battle_state !== BattleStates::FINISHED) {
            return trans('seo.battle_description', $this->versusReplacements($creations[0], $creations[1]));
        }

        $winner = $creations->first(fn (Track|Design $creation) => $creation->is($battle->winner));

        if (! $winner) {
            return trans('seo.battle_draw_description', $this->versusReplacements($creations[0], $creations[1]));
        }

        $loser = $creations->first(fn (Track|Design $creation) => $creation->isNot($winner));

        return trans('seo.battle_won_description', $this->versusReplacements($winner, $loser));
    }

    /**
     * @return array{first: string, first_author: string, second: string, second_author: string, app: string}
     */
    private function versusReplacements(Track|Design $first, Track|Design $second): array
    {
        return [
            'first' => $first->name,
            'first_author' => $first->createdBy?->getNickName() ?? trans('seo.unknown_author'),
            'second' => $second->name,
            'second_author' => $second->createdBy?->getNickName() ?? trans('seo.unknown_author'),
            'app' => config('app.name'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function creationReplacements(string $name, ?User $author): array
    {
        return [
            'name' => $name,
            'author' => $author?->getNickName() ?? trans('seo.unknown_author'),
            'app' => config('app.name'),
        ];
    }

    /**
     * Descriptions come from a rich text editor. A space goes before each tag so that paragraphs
     * do not run into each other once the tags are removed.
     */
    private function description(?string $text, string $fallback): string
    {
        $plainText = Str::of(html_entity_decode(strip_tags(str_replace('<', ' <', (string) $text)), ENT_QUOTES | ENT_HTML5))
            ->squish();

        return $plainText->isEmpty()
            ? $fallback
            : $plainText->limit(self::DESCRIPTION_LENGTH)->toString();
    }
}
```

**lang/es/seo.php** (un fragmento)

```php title="lang/es/seo.php"
return [
    'title' => ':page | :app',
    'home_title' => ':app | La red social de los artistas',
    'unknown_author' => 'un artista',
    'pages' => [
        'beats' => [
            'title' => 'Beats',
            'description' => 'Escucha beats de productores independientes y encuentra el de tu próximo tema.',
        ],
        // ...
    ],
    'track_title' => ':name, :type de :author',
    'track_description' => 'Escucha «:name», :type de :author en :app.',
    'battle_title' => ':first vs :second',
    'battle_description' => '«:first», de :first_author, contra «:second», de :second_author. Escucha las dos y vota la mejor en :app.',
    // ...
];
```

¡Y ya está!

Ahora cada página de la SPA llega a Google y a las redes sociales con su propio título, su descripción, su imagen y sus migas de pan.
Lo que no existe devuelve un 404, lo privado no se indexa y el sitemap se mantiene solo. Y todo sin tocar una línea del frontend, salvo el título de la pestaña.
