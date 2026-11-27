---
title: 'Imágenes para compartir enlaces con Laravel'
description: Cómo dibujar con Laravel e Intervention Image la imagen que muestran WhatsApp, LinkedIn o X al compartir un enlace, y cómo conseguir que se actualice cuando cambia lo que muestra.
---

# Imágenes para compartir enlaces con Laravel

## Introducción {#intro}

Cuando compartes un enlace en WhatsApp, Telegram, LinkedIn, X o Slack, aparece una tarjeta con un título, una descripción y una imagen.
Esa tarjeta es lo primero que ve quien recibe el enlace, y de ella depende en buena parte que haga clic o no.

En [Talent Battles](https://talentbattles.com) dos creaciones (dos beats, dos canciones, dos samples o dos diseños) se enfrentan y la comunidad vota cuál es mejor.
Al compartir una batalla, la tarjeta mostraba la imagen genérica de la web, así que no contaba lo importante: que es un enfrentamiento.

La solución fue dibujar desde Laravel una imagen para cada batalla, con las dos portadas cara a cara:

![Imagen para compartir de una batalla](https://zalospace.com/images/tutorials/share_image_battle.jpg)

En este tutorial voy a explicar cómo la dibujo con [Intervention Image](https://image.intervention.io/), cómo la guardo para no dibujarla en cada petición
y cómo consigo que las redes sociales muestren la nueva cuando algo cambia.

### El porqué de este tutorial

Dibujar la imagen es la parte vistosa, pero no la más complicada. Hay tres problemas que no se ven a primera vista:

- **Las redes sociales no ejecutan JavaScript.** Si tu web es una SPA (Talent Battles está hecha con Vue), las meta tags tienen que salir del servidor.
- **Las redes sociales guardan la imagen por su URL.** Si cambias la portada pero la URL de la imagen es la misma, seguirán mostrando la antigua.
- **Nginx puede quedarse con tu ruta.** Si la URL termina en `.jpg`, es muy probable que nginx la trate como un fichero estático y la petición nunca llegue a Laravel.

> [!NOTE]
> Todo el código de este tutorial está sacado de Talent Battles. Adáptalo a tus modelos: lo importante son las ideas.

## Requisitos {#requirements}

- [Laravel](https://laravel.com/): uso Laravel 13 con PHP 8.4, pero vale cualquier versión reciente.
- [Intervention Image 4](https://image.intervention.io/): la librería para crear y editar imágenes en PHP.
- La extensión **GD** de PHP. Intervention también funciona con Imagick, pero en este tutorial usaré GD.
- Un fichero de fuente `.ttf` u `.otf`. Para escribir texto de un tamaño decente, GD necesita el fichero de la fuente. Yo uso [DejaVu Sans](https://dejavu-fonts.github.io/) en negrita, que es libre.

```bash
composer require intervention/image
```

> [!WARNING]
> Intervention Image 4 cambió muchos nombres respecto a la versión 3: `createImage()` en lugar de `create()`, `decode()` y `decodeBinary()` en lugar de `read()`,
> `insert()` en lugar de `place()`, `encodeUsingMediaType()` en lugar de `toJpeg()`... Si buscas ejemplos por internet, fíjate bien en la versión.

## Cómo leen las redes un enlace {#open_graph}

Cuando alguien comparte un enlace, la red social descarga el HTML de la página y busca las meta tags del protocolo [Open Graph](https://ogp.me/).
Las que nos interesan para la imagen son estas:

```html
<meta property="og:image" content="https://talentbattles.com/battles/1/share-image?v=3f2a9c81b0d4">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
```

- **1200×630** (proporción 1,91:1) es el tamaño que recomienda Facebook para la tarjeta grande. LinkedIn usa la misma proporción y X lo muestra bien con `summary_large_image`.
- **`og:image:width` y `og:image:height`** le dicen el tamaño al rastreador para que pueda mostrar la imagen sin tener que descargarla antes.
- **JPEG** pesa mucho menos que PNG para imágenes con fotos. Con calidad 85, la imagen de arriba se queda en unos 67 KB.

> [!NOTE]
> El rastreador no ejecuta JavaScript: estas meta tags tienen que estar en el HTML que devuelve el servidor, no las puede añadir Vue después.

## Setup {#setup}

Lo primero es registrar el `ImageManager` de Intervention como singleton, indicando el driver que vamos a usar.
Así podremos inyectarlo en cualquier clase.

```php title="app/Providers/AppServiceProvider.php"
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

public function register(): void
{
    $this->app->singleton(ImageManager::class, fn () => ImageManager::usingDriver(Driver::class));
}
```

Toda la lógica irá en un servicio, `BattleShareImageService`, que tendrá tres responsabilidades:

- **Dibujar** la imagen.
- **Guardarla** y saber cuándo hay que volver a dibujarla.
- **Darnos la URL** que pondremos en `og:image`.

```bash
php artisan make:class Services/BattleShareImageService
```

## Dibujar la imagen {#draw}

Antes de escribir código, conviene hacer cuentas. La imagen mide 1200×630 y quiero dos portadas cuadradas de 400×400 con hueco para el "VS" en medio:

- La portada izquierda empieza en `x = 80` y termina en `x = 480`.
- La portada derecha empieza en `x = 720` y termina en `x = 1120`.
- Quedan 80 píxeles de margen a cada lado y 240 en el centro, donde irá el "VS" (centrado en `x = 600`).
- En vertical: el nombre de la web arriba, las portadas desde `y = 95` hasta `y = 495`, y debajo el nombre de cada creación y su autor.

Estas medidas las guardo en constantes, para no tener números mágicos repartidos por el código:

```php title="app/Services/BattleShareImageService.php"
public const WIDTH = 1200;

public const HEIGHT = 630;

private const COVER_SIZE = 400;

private const COVER_TOP = 95;

/**
 * Left edge of each cover: the left one and the right one, with room for "VS" between them.
 */
private const COVER_LEFTS = [80, 720];

private const BACKGROUND = '#121212';

private const EMPTY_COVER = '#1E1E1E';

private const ACCENT = '#787AF6';
```

Ahora sí, el método que dibuja. Creamos un lienzo del color de fondo, escribimos el nombre de la web, dibujamos cada creación y ponemos el "VS" en el centro:

```php title="app/Services/BattleShareImageService.php"
private function draw(Battle $battle): string
{
    $creations = $this->creations($battle);
    $image = $this->imageManager->createImage(self::WIDTH, self::HEIGHT)->fill(self::BACKGROUND);

    $image->text(Str::upper((string) config('app.name')), self::WIDTH / 2, 50, fn (FontFactory $font) => $font
        ->filename(resource_path('fonts/belmont_regular.otf'))
        ->size(34)
        ->color(self::ACCENT)
        ->align('center', 'center'));

    foreach (self::COVER_LEFTS as $index => $left) {
        $this->drawCreation($image, $creations->get($index), $left);
    }

    $image->text('VS', self::WIDTH / 2, self::COVER_TOP + self::COVER_SIZE / 2, fn (FontFactory $font) => $font
        ->filename(resource_path('fonts/belmont_regular.otf'))
        ->size(120)
        ->color('#FFFFFF')
        ->align('center', 'center'));

    return $image->encodeUsingMediaType('image/jpeg', quality: 85)->toString();
}
```

> [!NOTE]
> Con `align('center', 'center')` las coordenadas que pasamos a `text()` son el centro del texto, no su esquina. Así centrar es tan fácil como usar `WIDTH / 2`.

### Cada creación

En Talent Battles una batalla tiene dos creaciones, que pueden ser un `Track` o un `Design`. Cada una tiene nombre, color, portada (`image`), álbum y autor (`createdBy`).

Para cada creación hay tres casos posibles:

- **Tiene portada:** la recortamos a un cuadrado y la pegamos.
- **No tiene portada, o no se puede leer:** pintamos un cuadrado con su color.
- **La batalla aún espera rival:** pintamos un hueco gris con una interrogación.

```php title="app/Services/BattleShareImageService.php"
private function drawCreation(ImageInterface $image, Track|Design|null $creation, int $left): void
{
    $center = $left + self::COVER_SIZE / 2;

    if (! $creation) {
        $image->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle
            ->at($left, self::COVER_TOP)
            ->size(self::COVER_SIZE, self::COVER_SIZE)
            ->background(self::EMPTY_COVER));
        $this->write($image, '?', $center, self::COVER_TOP + self::COVER_SIZE / 2, 160, '#555555', 'center');

        return;
    }

    $cover = $this->coverImage($creation);

    if ($cover) {
        $image->insert($cover, $left, self::COVER_TOP);
    } else {
        $image->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle
            ->at($left, self::COVER_TOP)
            ->size(self::COVER_SIZE, self::COVER_SIZE)
            ->background($creation->color ?: self::ACCENT));
    }

    $this->write($image, Str::limit($creation->name, 22), $center, 525, 30, '#FFFFFF');
    $this->write($image, Str::limit((string) $creation->createdBy?->getNickName(), 30), $center, 572, 22, '#9E9E9E');
}
```

Así queda una batalla que todavía espera rival:

![Imagen para compartir de una batalla sin rival](https://zalospace.com/images/tutorials/share_image_waiting.jpg)

> [!TIP]
> `Str::limit()` corta los nombres largos y les añade "...". Sin él, un nombre muy largo se saldría de su mitad de la imagen y se montaría sobre el "VS".

### La portada

La portada de una creación puede ser la suya, la de su álbum o la foto de su autor. Es la misma que usa la página de la creación cuando se comparte.

Para pegarla usamos `cover()`, que funciona como el `object-fit: cover` de CSS: escala la imagen y recorta lo que sobra para llenar el cuadrado sin deformarla.

```php title="app/Services/BattleShareImageService.php"
/**
 * The cover cropped to a square. A cover that cannot be read is left out, so the battle can still be shared.
 */
private function coverImage(Track|Design $creation): ?ImageInterface
{
    $file = $this->cover($creation);

    if (! $file || ! Storage::exists($file->path)) {
        return null;
    }

    try {
        return $this->imageManager
            ->decodeBinary((string) Storage::get($file->path))
            ->cover(self::COVER_SIZE, self::COVER_SIZE);
    } catch (Exception $exception) {
        Logger::exception($exception, ['file' => $file->id]);

        return null;
    }
}

/**
 * The same image the page of the creation shares: its own, its album's or its author's.
 */
private function cover(Track|Design $creation): ?File
{
    return $creation->image ?? $creation->album->image ?? $creation->createdBy?->image;
}
```

> [!WARNING]
> Captura siempre las excepciones al leer imágenes subidas por usuarios. Un fichero corrupto no debería impedir que se comparta la batalla: en ese caso pintamos el color de la creación y lo dejamos registrado en el log.

Uso `decodeBinary()` con el contenido que devuelve `Storage::get()`, en lugar de pasarle una ruta, porque así funciona igual con el disco local que con S3.

## Guardar la imagen y versionarla {#version}

Dibujar la imagen cuesta: hay que leer dos portadas (quizá de S3), decodificarlas, recortarlas y codificar un JPEG.
No tiene sentido hacerlo cada vez que alguien la pida, así que la guardamos en el disco y solo la volvemos a dibujar cuando cambie lo que muestra.

Pero, ¿cómo sabemos que ha cambiado? Con una **versión**: un hash de todo lo que aparece en la imagen.

```php title="app/Services/BattleShareImageService.php"
/**
 * Changes whenever something the image shows changes: a creation, its name, its author or its cover. A cover is
 * replaced in place, keeping its id, so its path is what tells it has changed.
 */
public function version(Battle $battle): string
{
    $shown = $this->creations($battle)->map(fn (Track|Design $creation) => [
        $creation->id,
        $creation->name,
        $creation->color,
        $creation->createdBy?->getNickName(),
        $this->cover($creation)?->path,
    ]);

    return substr(md5((string) json_encode($shown)), 0, 12);
}
```

Si cambia el nombre de una creación, su color, el nick de su autor o su portada, cambia el hash. Si no cambia nada, el hash es el mismo.

> [!WARNING]
> Fíjate en que de la portada uso su ruta (`path`) y no su `id`. En Talent Battles, al cambiar una portada se reutiliza el mismo registro de la tabla `files` y solo cambia la ruta del fichero.
> Con el `id`, la versión no cambiaría y seguiríamos mostrando la portada antigua. Mete en el hash algo que cambie siempre que cambie lo que se ve.

La versión forma parte del nombre del fichero. Si el fichero de la versión actual no existe, borramos las versiones anteriores de esa batalla y dibujamos la nueva:

```php title="app/Services/BattleShareImageService.php"
private const DIRECTORY = 'battles/share';

/**
 * The path of the image on the default disk, drawn first if this version is not there yet. Older versions of the
 * image of the battle are deleted.
 */
public function path(Battle $battle): string
{
    $path = self::DIRECTORY."/{$battle->id}-{$this->version($battle)}.jpg";

    if (! Storage::exists($path)) {
        Storage::delete(collect(Storage::files(self::DIRECTORY))
            ->filter(fn (string $file) => Str::startsWith(basename($file), "{$battle->id}-"))
            ->all());

        Storage::put($path, $this->draw($battle));
    }

    return $path;
}
```

La imagen se dibuja la primera vez que alguien la pide, que normalmente será el rastreador de una red social. Esa primera petición tarda un poco más y las siguientes solo leen el fichero.

> [!TIP]
> `Storage::files()` lista todas las imágenes de todas las batallas. Si tienes miles, guarda cada batalla en su propia carpeta (`battles/share/{id}/{version}.jpg`)
> y así solo tendrás que listar los ficheros de esa batalla.

### La versión en la URL

Aquí está el truco más importante del tutorial. Las redes sociales guardan la imagen asociada a su URL, y no hay una forma fiable de pedirles a todas que la vuelvan a descargar.

La solución es que la URL cambie cuando cambia la imagen. Para eso añadimos la versión como parámetro:

```php title="app/Services/BattleShareImageService.php"
public function url(Battle $battle): string
{
    return route('battles.share_image', ['battle' => $battle, 'v' => $this->version($battle)]);
}
```

Esto genera URLs como `https://talentbattles.com/battles/1/share-image?v=3f2a9c81b0d4`. Para la red social, una versión nueva es una imagen que no ha visto nunca, así que la descarga.

Para que todo esto funcione sin consultas de más, cargamos de golpe las relaciones que vamos a usar:

```php title="app/Services/BattleShareImageService.php"
/**
 * @return Collection<int, Track>|Collection<int, Design>
 */
private function creations(Battle $battle): Collection
{
    $battle->loadMissing([
        'tracks.image', 'tracks.album.image', 'tracks.createdBy.image',
        'designs.image', 'designs.album.image', 'designs.createdBy.image',
    ]);

    return $battle->getCreations()->values()->toBase();
}
```

> [!NOTE]
> `loadMissing()` solo carga las relaciones que no estén ya cargadas, así que podemos llamar a `creations()` varias veces (desde `version()` y desde `draw()`) sin repetir consultas.

## Servir la imagen {#serve}

El controlador es muy sencillo: devuelve el JPEG con sus cabeceras de caché.

```php title="app/Http/Controllers/BattleShareImageController.php"
class BattleShareImageController extends Controller
{
    /**
     * The image of a battle for link previews. Its URL carries the version it shows, so that URL can be cached for
     * good; an old or missing version gets the current image, cached for an hour.
     */
    public function __invoke(Battle $battle, BattleShareImageService $shareImageService): Response
    {
        $isCurrentVersion = request('v') === $shareImageService->version($battle);

        return response((string) Storage::get($shareImageService->path($battle)), Response::HTTP_OK, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => $isCurrentVersion ? 'public, max-age=31536000, immutable' : 'public, max-age=3600',
        ]);
    }
}
```

Hay dos casos:

- **La URL trae la versión actual.** Esa URL siempre devolverá la misma imagen, así que se puede cachear para siempre (`immutable`, un año).
- **La URL trae una versión antigua o ninguna.** Por ejemplo, un enlace compartido antes de que cambiara la portada. No devolvemos un 404: devolvemos la imagen actual, pero cacheada solo una hora.

### La ruta, sin extensión

```php title="routes/web.php"
/**
 * Without an extension: nginx serves paths ending in .jpg as static files and never hands them to Laravel.
 */
Route::get('battles/{battle}/share-image', BattleShareImageController::class)
    ->whereNumber('battle')
    ->name('battles.share_image');
```

> [!WARNING]
> Mi primera versión de la ruta era `battles/{battle}/share.jpg`. Los tests pasaban, pero en producción devolvía un 404 y las batallas se compartían con la imagen rota.

El motivo es que muchas configuraciones de nginx tienen un bloque parecido a este, para servir los ficheros estáticos con caché larga:

```nginx
location ~* \.(jpg|jpeg|png|gif|webp|svg|css|js|ico|woff2)$ {
    expires max;
    access_log off;
}
```

Cualquier URL que termine en `.jpg` entra en ese bloque. Nginx busca el fichero en `public/`, no lo encuentra y devuelve un 404 sin pasarle la petición a Laravel.

Podría haber cambiado la configuración de nginx, pero es más sencillo y más seguro que la ruta no tenga extensión: funciona en cualquier servidor.
Las redes sociales no miran la extensión, se fían de la cabecera `Content-Type`.

> [!TIP]
> Añade un test que compruebe que la URL no tiene extensión. Así nadie (ni tú dentro de un año) volverá a ponérsela.

## Las meta tags {#meta_tags}

Solo queda poner la URL en `og:image`. Talent Battles es una SPA con Vue, pero cada página la sirve Laravel con sus propias meta tags.
Para una batalla, la imagen de las meta tags es la que acabamos de dibujar:

```php title="app/Services/PageMetaService.php"
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
```

Y la vista pinta las meta tags a partir de ese objeto:

```blade title="resources/views/partials/meta.blade.php"
<meta property="og:title" content="{{ $meta->title }}">
<meta property="og:description" content="{{ $meta->description }}">
<meta property="og:image" content="{{ $meta->image }}">
<meta property="og:image:type" content="{{ $meta->imageType }}">
@if($meta->imageWidth && $meta->imageHeight)
    <meta property="og:image:width" content="{{ $meta->imageWidth }}">
    <meta property="og:image:height" content="{{ $meta->imageHeight }}">
@endif
<meta name="twitter:card" content="summary_large_image">
```

> [!NOTE]
> Cómo sirvo una SPA de Vue con meta tags distintas para cada página (título, descripción, JSON-LD, sitemap...) lo explico en el tutorial [SEO para una SPA de Vue servida por Laravel](/tutorials/es/spa_seo).

## Tests {#tests}

Una imagen es difícil de testear "a ojo", pero sí podemos comprobar lo que importa. En los tests uso `Storage::fake()` y un helper, `battle()`, que crea una batalla con dos tracks.

Que la imagen se sirve como JPEG, con el tamaño correcto y cacheada para siempre:

```php title="tests/Feature/Battles/BattleShareImageTest.php"
#[Test]
public function a_battle_is_shared_with_a_jpeg_of_its_two_creations_cached_for_good()
{
    $battle = $this->battle();

    $response = $this->get($this->service()->url($battle))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));

    $image = $this->decode($response->getContent());
    $this->assertSame(BattleShareImageService::WIDTH, $image->width());
    $this->assertSame(BattleShareImageService::HEIGHT, $image->height());
}
```

Que la URL no tiene extensión, por lo que contaba antes de nginx:

```php title="tests/Feature/Battles/BattleShareImageTest.php"
#[Test]
public function the_image_url_has_no_extension_so_nginx_hands_it_to_laravel()
{
    $path = (string) parse_url($this->service()->url($this->battle()), PHP_URL_PATH);

    $this->assertSame('', pathinfo($path, PATHINFO_EXTENSION));
}
```

Que la imagen se dibuja una sola vez, que se vuelve a dibujar cuando algo cambia y que la versión antigua se borra:

```php title="tests/Feature/Battles/BattleShareImageTest.php"
#[Test]
public function the_image_is_drawn_once_and_drawn_again_when_a_creation_changes()
{
    $battle = $this->battle();

    $first = $this->service()->path($battle);
    $this->assertSame($first, $this->service()->path($battle->fresh()));

    $battle->tracks->first()->update(['name' => 'Otro nombre']);
    $second = $this->service()->path($battle->fresh());

    $this->assertNotSame($first, $second);
    $this->assertSame([$second], Storage::files('battles/share'));
}
```

### Comprobar los píxeles

Este es mi favorito. Para saber si la portada se ha pegado bien, creo una portada roja de 50×50, dibujo la imagen y miro el color del píxel que cae en el centro de la portada izquierda:

```php title="tests/Feature/Battles/BattleShareImageTest.php"
/**
 * The middle of the left cover in the image.
 */
private const LEFT_COVER_CENTER = [280, 295];

#[Test]
public function a_creation_is_shown_with_its_cover()
{
    $battle = $this->battle();
    Storage::put('images/cover.jpg', app(ImageManager::class)->createImage(50, 50)->fill('#FF0000')->encodeUsingMediaType('image/jpeg')->toString());
    File::factory()->image()->for($battle->tracks->first(), 'fileable')->create(['path' => 'images/cover.jpg', 'mime' => 'image/jpeg']);

    $this->assertLeftCoverIs([255, 0, 0], $battle->fresh());
}

/**
 * @param  array{int, int, int}  $rgb
 */
private function assertLeftCoverIs(array $rgb, Battle $battle): void
{
    $image = $this->decode((string) Storage::get($this->service()->path($battle)));
    $color = sscanf($image->colorAt(...self::LEFT_COVER_CENTER)->toHex(), '%02x%02x%02x');

    foreach ($rgb as $channel => $value) {
        $this->assertEqualsWithDelta($value, $color[$channel], 30, 'The JPEG keeps the color of the cover, give or take');
    }
}
```

Las coordenadas salen de las cuentas del principio: la portada izquierda va de `x = 80` a `x = 480` y de `y = 95` a `y = 495`, así que su centro es `(280, 295)`.

> [!NOTE]
> JPEG comprime con pérdida, así que el rojo puro `(255, 0, 0)` puede salir como `(254, 0, 2)`. Por eso comparo cada canal con `assertEqualsWithDelta()` y un margen de 30.

Con el mismo helper compruebo que una portada corrupta (un fichero que dice ser JPEG pero contiene texto) se sustituye por el color de la creación.

## Probarlo de verdad {#check}

Antes de compartir nada, comprueba que la imagen responde bien:

```bash
curl -I "https://tu-web.com/battles/1/share-image?v=3f2a9c81b0d4"
```

Deberías ver un `200` con `Content-Type: image/jpeg` y `Cache-Control: immutable, max-age=31536000, public`. Si ves un 404, revisa lo que comentaba de nginx.

Para ver la tarjeta tal y como la verán los demás, cada red tiene su herramienta:

- [Sharing Debugger de Facebook](https://developers.facebook.com/tools/debug/): muestra las meta tags que lee y permite forzar que vuelva a leer la página.
- [Post Inspector de LinkedIn](https://www.linkedin.com/post-inspector/): lo mismo para LinkedIn.

> [!TIP]
> Estas herramientas sirven para comprobar que todo funciona, pero no puedes pedirle a cada persona que comparte un enlace que fuerce la recarga. Por eso la versión va en la URL.

## Código {#code}

**app/Services/BattleShareImageService.php**

```php title="app/Services/BattleShareImageService.php"
<?php

namespace App\Services;

use App\Facades\Logger;
use App\Models\Battle;
use App\Models\Design;
use App\Models\File;
use App\Models\Track;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

/**
 * The image shared with a link to a battle: its two creations face to face, at the 1200×630 of every link preview.
 * It is drawn once for each version of what it shows and kept on the default disk. The version goes in its URL, so
 * social networks fetch it again when a creation changes.
 */
class BattleShareImageService
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const DIRECTORY = 'battles/share';

    private const COVER_SIZE = 400;

    private const COVER_TOP = 95;

    /**
     * Left edge of each cover: the left one and the right one, with room for "VS" between them.
     */
    private const COVER_LEFTS = [80, 720];

    private const BACKGROUND = '#121212';

    private const EMPTY_COVER = '#1E1E1E';

    private const ACCENT = '#787AF6';

    public function __construct(private readonly ImageManager $imageManager) {}

    public function url(Battle $battle): string
    {
        return route('battles.share_image', ['battle' => $battle, 'v' => $this->version($battle)]);
    }

    /**
     * Changes whenever something the image shows changes: a creation, its name, its author or its cover. A cover is
     * replaced in place, keeping its id, so its path is what tells it has changed.
     */
    public function version(Battle $battle): string
    {
        $shown = $this->creations($battle)->map(fn (Track|Design $creation) => [
            $creation->id,
            $creation->name,
            $creation->color,
            $creation->createdBy?->getNickName(),
            $this->cover($creation)?->path,
        ]);

        return substr(md5((string) json_encode($shown)), 0, 12);
    }

    /**
     * The path of the image on the default disk, drawn first if this version is not there yet. Older versions of the
     * image of the battle are deleted.
     */
    public function path(Battle $battle): string
    {
        $path = self::DIRECTORY."/{$battle->id}-{$this->version($battle)}.jpg";

        if (! Storage::exists($path)) {
            Storage::delete(collect(Storage::files(self::DIRECTORY))
                ->filter(fn (string $file) => Str::startsWith(basename($file), "{$battle->id}-"))
                ->all());

            Storage::put($path, $this->draw($battle));
        }

        return $path;
    }

    private function draw(Battle $battle): string
    {
        $creations = $this->creations($battle);
        $image = $this->imageManager->createImage(self::WIDTH, self::HEIGHT)->fill(self::BACKGROUND);

        $image->text(Str::upper((string) config('app.name')), self::WIDTH / 2, 50, fn (FontFactory $font) => $font
            ->filename(resource_path('fonts/belmont_regular.otf'))
            ->size(34)
            ->color(self::ACCENT)
            ->align('center', 'center'));

        foreach (self::COVER_LEFTS as $index => $left) {
            $this->drawCreation($image, $creations->get($index), $left);
        }

        $image->text('VS', self::WIDTH / 2, self::COVER_TOP + self::COVER_SIZE / 2, fn (FontFactory $font) => $font
            ->filename(resource_path('fonts/belmont_regular.otf'))
            ->size(120)
            ->color('#FFFFFF')
            ->align('center', 'center'));

        return $image->encodeUsingMediaType('image/jpeg', quality: 85)->toString();
    }

    /**
     * The cover of the creation with its name and author below. A battle still waiting for a rival shows a question
     * mark in the empty place.
     */
    private function drawCreation(ImageInterface $image, Track|Design|null $creation, int $left): void
    {
        $center = $left + self::COVER_SIZE / 2;

        if (! $creation) {
            $image->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle
                ->at($left, self::COVER_TOP)
                ->size(self::COVER_SIZE, self::COVER_SIZE)
                ->background(self::EMPTY_COVER));
            $this->write($image, '?', $center, self::COVER_TOP + self::COVER_SIZE / 2, 160, '#555555', 'center');

            return;
        }

        $cover = $this->coverImage($creation);

        if ($cover) {
            $image->insert($cover, $left, self::COVER_TOP);
        } else {
            $image->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle
                ->at($left, self::COVER_TOP)
                ->size(self::COVER_SIZE, self::COVER_SIZE)
                ->background($creation->color ?: self::ACCENT));
        }

        $this->write($image, Str::limit($creation->name, 22), $center, 525, 30, '#FFFFFF');
        $this->write($image, Str::limit((string) $creation->createdBy?->getNickName(), 30), $center, 572, 22, '#9E9E9E');
    }

    private function write(ImageInterface $image, string $text, int $x, int $y, int $size, string $color, string $vertical = 'top'): void
    {
        $image->text($text, $x, $y, fn (FontFactory $font) => $font
            ->filename(resource_path('fonts/dejavu_sans_bold.ttf'))
            ->size($size)
            ->color($color)
            ->align('center', $vertical));
    }

    /**
     * The cover cropped to a square. A cover that cannot be read is left out, so the battle can still be shared.
     */
    private function coverImage(Track|Design $creation): ?ImageInterface
    {
        $file = $this->cover($creation);

        if (! $file || ! Storage::exists($file->path)) {
            return null;
        }

        try {
            return $this->imageManager
                ->decodeBinary((string) Storage::get($file->path))
                ->cover(self::COVER_SIZE, self::COVER_SIZE);
        } catch (Exception $exception) {
            Logger::exception($exception, ['file' => $file->id]);

            return null;
        }
    }

    /**
     * The same image the page of the creation shares: its own, its album's or its author's.
     */
    private function cover(Track|Design $creation): ?File
    {
        return $creation->image ?? $creation->album->image ?? $creation->createdBy?->image;
    }

    /**
     * @return Collection<int, Track>|Collection<int, Design>
     */
    private function creations(Battle $battle): Collection
    {
        $battle->loadMissing([
            'tracks.image', 'tracks.album.image', 'tracks.createdBy.image',
            'designs.image', 'designs.album.image', 'designs.createdBy.image',
        ]);

        return $battle->getCreations()->values()->toBase();
    }
}
```

> [!NOTE]
> `Logger` es una facade propia de Talent Battles. Puedes cambiarla por `report($exception)` o por `Log::error()`.

**app/Http/Controllers/BattleShareImageController.php**

```php title="app/Http/Controllers/BattleShareImageController.php"
<?php

namespace App\Http\Controllers;

use App\Models\Battle;
use App\Services\BattleShareImageService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class BattleShareImageController extends Controller
{
    /**
     * The image of a battle for link previews. Its URL carries the version it shows, so that URL can be cached for
     * good; an old or missing version gets the current image, cached for an hour.
     */
    public function __invoke(Battle $battle, BattleShareImageService $shareImageService): Response
    {
        $isCurrentVersion = request('v') === $shareImageService->version($battle);

        return response((string) Storage::get($shareImageService->path($battle)), Response::HTTP_OK, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => $isCurrentVersion ? 'public, max-age=31536000, immutable' : 'public, max-age=3600',
        ]);
    }
}
```

**routes/web.php**

```php title="routes/web.php"
Route::get('battles/{battle}/share-image', BattleShareImageController::class)
    ->whereNumber('battle')
    ->name('battles.share_image');
```

¡Y ya está!

Ahora, cada vez que alguien comparte una batalla, la tarjeta muestra las dos creaciones cara a cara.
Si cambia una portada, un nombre o un autor, la URL de la imagen cambia con ellos y las redes descargan la nueva.
