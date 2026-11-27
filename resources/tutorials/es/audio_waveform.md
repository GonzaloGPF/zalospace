---
title: 'Ondas de audio con Laravel, audiowaveform y D3'
description: Cómo generar en el servidor la forma de onda de cada audio que se sube, guardarla en unos pocos KB y dibujarla en Vue con D3, con barra de progreso y clic para saltar.
---

# Ondas de audio con Laravel, audiowaveform y D3

## Introducción {#intro}

En [Talent Battles](https://talentbattles.com) cada beat, canción o sample se muestra con su forma de onda. De un vistazo ves dónde entra la batería, dónde baja la intensidad o dónde termina la pista, y puedes hacer clic en cualquier punto para saltar ahí.

![Reproductor de un track con su forma de onda](https://zalospace.com/images/tutorials/waveform_player.png)

En este tutorial voy a explicar cómo funciona de principio a fin:

- Cuando alguien sube un audio, un job de Laravel genera los datos de la onda con [audiowaveform](https://github.com/bbc/audiowaveform), una herramienta de la BBC.
- Esos datos se guardan en la base de datos como un JSON de unos pocos KB.
- En el navegador, [waveform-data](https://github.com/bbc/waveform-data.js) lee ese JSON y [D3](https://d3js.org/) dibuja la onda en un SVG.
- Encima de la onda, una capa marca lo que ya ha sonado, y un clic salta a ese punto.

### El porqué de este tutorial

El navegador puede calcular la onda por sí mismo con la Web Audio API (`decodeAudioData()`). Pero para eso necesita **descargar el audio entero** y decodificarlo, y lo tendría que hacer en cada visita.

En una lista con 20 beats, eso son 20 MP3 de varios MB solo para pintar unas ondas, y además antes de que nadie le dé a reproducir.

Con la onda calculada en el servidor, el navegador solo descarga unos pocos KB de JSON por pista:

| Pista de 3 minutos | Tamaño |
| :- | :- |
| MP3 a 128 kbps | 2,8 MB |
| JSON de la onda | 6,3 KB |

> [!NOTE]
> Son unas 450 veces menos. Y el audio solo se descarga cuando alguien le da a reproducir.

## Requisitos {#requirements}

- [Laravel](https://laravel.com/) con colas. Uso Laravel 13 con PHP 8.4.
- [audiowaveform](https://github.com/bbc/audiowaveform) instalado en el servidor.
- [Vue 3](https://vuejs.org/) en el frontend, con [D3](https://d3js.org/) y [waveform-data](https://github.com/bbc/waveform-data.js).

En Ubuntu, audiowaveform se instala desde un PPA. En Debian, descarga el `.deb` de sus [releases](https://github.com/bbc/audiowaveform/releases):

```bash
sudo add-apt-repository -y ppa:chris-needham/ppa
sudo apt-get update
sudo apt-get install -y audiowaveform
```

Si usas Laravel Sail, añádelo al `Dockerfile` del contenedor, en el mismo `apt-get install` que el resto de paquetes:

```dockerfile title="docker/8.4/Dockerfile"
    && add-apt-repository -y ppa:chris-needham/ppa \
    && apt-get install -y audiowaveform \
```

Y en el frontend:

```bash
yarn add d3 waveform-data
```

## Cómo son los datos de una onda {#data}

Antes de escribir código, merece la pena entender qué genera audiowaveform. Pruébalo desde la terminal con cualquier MP3:

```bash
audiowaveform -i beat.mp3 -o beat.json -b 8 -z 8192
```

- **`-b 8`**: guarda cada valor con 8 bits, entre -128 y 127. Para dibujar una onda de 60 píxeles de alto sobra, y los números del JSON son mucho más cortos que con 16 bits (de -32768 a 32767).
- **`-z 8192`**: el *zoom*, es decir, cuántas muestras de audio se resumen en cada punto de la onda. Un audio a 44,1 kHz tiene 44.100 muestras por segundo, así que cada punto resume unos 0,19 segundos.

El resultado es un JSON como este:

```json
{
    "version": 2,
    "channels": 1,
    "sample_rate": 44100,
    "samples_per_pixel": 8192,
    "bits": 8,
    "length": 324,
    "data": [0, 0, 0, 0, -1, 1, -2, 2, -2, 2, -3, 3, ...]
}
```

La clave está en `data`: por cada punto (audiowaveform los llama *píxeles*) guarda **dos valores, el mínimo y el máximo** de las 8192 muestras que resume. Por eso `data` tiene el doble de valores que `length`.

![Mínimo y máximo de cada píxel de una onda](https://zalospace.com/images/tutorials/waveform_min_max.png)

En la imagen se ve el momento en que entra el bombo: hasta ahí solo suena un acorde suave (valores entre -17 y 17), y de repente aparecen picos de cerca de ±80.
Para dibujar la onda solo hay que unir todos los máximos por arriba y todos los mínimos por abajo, y rellenar lo que queda en medio.

El tamaño es fácil de calcular: `length = ceil(segundos × 44100 / 8192)`.

| Duración | `length` | Valores en `data` |
| :- | :- | :- |
| 20 segundos | 108 | 216 |
| 1 minuto | 324 | 648 |
| 3 minutos | 970 | 1940 |

> [!NOTE]
> audiowaveform mezcla los canales en uno por defecto, por eso `channels` es 1 aunque el audio sea estéreo. Para dibujar una onda en una web es justo lo que queremos.

## Guardar la onda {#storage}

La onda va en una columna JSON de la tabla `tracks`:

```php title="database/migrations/2020_10_02_133048_create_tracks_table.php"
$table->json('waveform')->nullable();
```

En el modelo la casteamos a array y la ocultamos:

```php title="app/Models/Track.php"
protected $hidden = [
    'waveform',
];

protected $casts = [
    // ...
    'waveform' => 'array',
];
```

¿Por qué oculta? Porque aunque sean pocos KB, no la queremos en todas partes: ni en las notificaciones, ni en los logs, ni en las tablas del panel de administración. Solo cuando vamos a pintarla.

Para eso, el API Resource del track solo la incluye si la petición la pide:

```php title="app/Http/Resources/TrackResource.php"
'waveform' => $this->when($request->get('waveform'), $this->waveform),
```

Y el frontend la pide añadiendo `waveform: true` a los parámetros de las listas y las páginas que muestran el reproductor:

```js title="resources/js/helpers/TrackHelper.js"
indexParams: {
    with: [
        'album.image',
        'image',
        'audio',
        'createdBy.image',
        // ...
    ],
    waveform: true
},
```

> [!TIP]
> En el panel de administración, la tabla de tracks reutiliza estos parámetros pero quita `waveform`, porque ahí no se pinta ninguna onda.

## Generar la onda al subir el audio {#generate}

### Cuándo: un observer y un job

Todos los ficheros que se suben pasan por el modelo `File`. Su observer mira el tipo de cada fichero nuevo y manda los audios a un job:

```php title="app/Observers/FileObserver.php"
public function saved(File $file): void
{
    // ...

    if (in_array($file->mime, FileService::$IMAGE_MIMES)) {
        ProcessImageJob::dispatch($file);
    }

    if (in_array($file->mime, FileService::$AUDIO_MIMES)) {
        ProcessAudioJob::dispatch($file);
    }
}
```

El job calcula la onda, la duración y el BPM del track, y los guarda de una vez:

```php title="app/Jobs/ProcessAudioJob.php"
public function handle(FileService $fileService): void
{
    if (! $fileService->isAudio($this->file)) { // Only audios will be processed
        return;
    }

    if (! Storage::exists($this->file->path)) {
        Logger::exception(new FileNotFoundException('Audio Not Found for Processing. Deleting'), $this->file->toArray());
        $this->file->delete();

        return;
    }

    if ($this->file->fileable_type === Track::class) {
        /** @var Track $track */
        $track = $this->file->fileable;

        $track->forceFill([
            'waveform' => $fileService->getWaveform($this->file),
            'duration' => $fileService->getDuration($this->file),
            'bpm' => $track->bpm ?? $fileService->getBPM($this->file),
        ])->save();
    }
}
```

> [!NOTE]
> El BPM solo se calcula si el usuario no lo ha indicado al subir el track. Si lo ha puesto, nos fiamos de él.

### Cómo: audiowaveform desde PHP

El trabajo de verdad lo hace `AudioHelper`. Tiene un pequeño problema que resolver: audiowaveform necesita un fichero en el disco local, y los audios pueden estar en S3.
Así que primero copia el audio a un disco temporal, ejecuta audiowaveform, lee el JSON y borra los dos ficheros:

```php title="app/Helpers/AudioHelper.php"
/**
 * It generates a file with waveform data.
 * After generating the file, it will read its content in $waveformData to return it.
 * Finally, it will delete generated file
 *
 * @return array<mixed>|string|null
 */
public function getWaveformData(File $file, string $format = self::FORMAT_JSON): array|string|null
{
    $filename = pathinfo($file->path, PATHINFO_FILENAME);
    $extension = pathinfo($file->path, PATHINFO_EXTENSION);
    $tmpFilePath = "$filename.$extension";

    $fullPath = $this->createTempFile($file->path);

    $directory = pathinfo($fullPath, PATHINFO_DIRNAME);
    $destiny = "$directory/$filename.$format";

    $this->process = resolve(Process::class, [
        'command' => ['audiowaveform', '-i', $fullPath, '-o', $destiny, '-b', '8', '-z', '8192'],
    ]);

    try {
        $this->process->run();

        $this->output = $this->getProcessOutput();

        $jsonFilePath = Str::replace(".$extension", ".$format", $tmpFilePath);

        if (! Storage::disk('tmp')->exists($jsonFilePath)) {
            Logger::error("File not found $jsonFilePath", ['output' => $this->output]);

            return null;
        }

        $waveformData = Storage::disk('tmp')->get($jsonFilePath);

        Storage::disk('tmp')->delete($jsonFilePath);
        Storage::disk('tmp')->delete($tmpFilePath);

        if ($format === self::FORMAT_JSON) {
            return (array) json_decode($waveformData);
        }

        return $waveformData;
    } catch (Exception $exception) {
        Logger::exception($exception);

        return null;
    }
}

/**
 * Creates a temporal file and returns its full path
 */
private function createTempFile(string $path): string
{
    $filename = pathinfo($path, PATHINFO_FILENAME);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $tmpFilePath = "$filename.$extension";

    Storage::disk('tmp')
        ->put($tmpFilePath, Storage::get($path));

    return Storage::disk('tmp')->path($tmpFilePath);
}
```

El disco temporal es un disco local más, definido en `config/filesystems.php`:

```php title="config/filesystems.php"
'tmp' => [
    'driver' => 'local',
    'root' => storage_path('app/files/tmp'),
    'throw' => false,
],
```

Hay varios detalles que conviene ver:

- **El comando es un array**, no un string. Symfony Process pasa cada elemento como un argumento, sin pasar por la shell, así que una ruta con espacios o caracteres raros no rompe nada ni permite inyectar comandos.
- **El `Process` sale del contenedor de Laravel** con `resolve()` en lugar de `new Process(...)`. Así, en los tests de otras clases se puede sustituir por un mock sin ejecutar audiowaveform.
- **audiowaveform deduce el formato de salida** por la extensión del fichero de destino. Con `.json` genera JSON, con `.dat` un formato binario más compacto y con `.png` una imagen.
- **Si algo falla, devuelve `null`** y lo deja en el log. El track se guarda igualmente, sin onda.

> [!TIP]
> Si audiowaveform no genera el fichero, el error está en `$this->output`, que guarda lo que el proceso escribió por la salida estándar o, si no hay nada, por la de errores. Por eso va en el contexto del log.

## Dibujar la onda con D3 {#draw}

Ya tenemos el JSON en el navegador. Ahora hay que convertir esos mínimos y máximos en un dibujo. Todo el código está en un objeto, `WaveDrawer`:

```js title="resources/js/objects/WaveDrawer.js"
import * as d3 from 'd3';
import WaveformData from 'waveform-data';

export default {
    draw(json, element, color = '#0D47A1') {
        if (!element) return;

        if (!Array.isArray(json?.data)) return;

        // Clean up old waveform, useful when resizing window
        d3.select(element)
            .selectAll('svg')
            .remove();

        let waveform = WaveformData.create(json);
        let channel = waveform.channel(0);
        let width = element.offsetWidth;
        let height = element.offsetHeight;

        const offsetX = height / 2;

        const min = channel.min_array();
        const max = channel.max_array();

        const x = d3.scaleLinear()
            .domain([0, waveform.length])
            .rangeRound([0, width]);

        const y = d3.scaleLinear()
            .domain([d3.min(min), d3.max(max)])
            .rangeRound([offsetX, -offsetX]);

        const area = d3.area()
            .x((d, i) => x(i))
            .y0((d, i) => y(min[i]))
            .y1((d) => y(d));

        if (d3.select(element).node()) {
            d3.select(element)
                .append('svg')
                .style('width', width + 'px')
                .style('height', height + 'px')
                .style('fill', color)
                .attr('class', 'waveform')
                .datum(max)
                .append('path')
                .attr('transform', () => `translate(0, ${offsetX})`)
                .attr('d', area);
        }
    },
}
```

Vamos por partes.

### waveform-data

`WaveformData.create(json)` lee el JSON de audiowaveform. Lo más útil es que separa los valores intercalados de `data` en dos arrays: `min_array()` con todos los mínimos y `max_array()` con todos los máximos.

### Las escalas

Una escala de D3 convierte un valor de los datos en una posición en pantalla:

- **`x`** convierte el índice de cada punto (de 0 a `length`) en un píxel horizontal (de 0 al ancho del elemento). La onda siempre ocupa todo el ancho, mida lo que mida.
- **`y`** convierte un valor de la onda en un píxel vertical. El rango va de `+offsetX` a `-offsetX` (la mitad de la altura hacia abajo y hacia arriba), porque en SVG el eje Y crece hacia abajo: el máximo tiene que quedar arriba.

Fíjate en el dominio de `y`: `[d3.min(min), d3.max(max)]`, el mínimo y el máximo **de esta pista**. Es decir, la onda se normaliza: una pista grabada muy flojita ocupa todo el alto igual que una muy fuerte.

> [!TIP]
> Si prefieres que las ondas se puedan comparar entre pistas (que una pista floja se vea más pequeña), usa el dominio fijo de 8 bits: `.domain([-128, 127])`.

### El área

`d3.area()` es lo que dibuja la forma. Recibe un array de puntos y, para cada uno, tres valores:

- **`x`**: su posición horizontal.
- **`y1`**: el borde de arriba, el máximo.
- **`y0`**: el borde de abajo, el mínimo del mismo índice.

Con `.datum(max)` le pasamos los máximos como datos, y el mínimo lo buscamos por índice en `min`. El resultado es un único `<path>` que D3 rellena con el color del track.

Por último, `translate(0, offsetX)` baja todo el dibujo a la mitad del elemento, porque las posiciones de `y` van de `-offsetX` a `+offsetX` y el SVG empieza en 0.

> [!NOTE]
> Lo primero que hace `draw()` es borrar el SVG anterior. Así podemos llamarla cada vez que cambia el tamaño de la ventana sin acumular ondas.

## El reproductor {#player}

`WaveDrawer` solo dibuja. El componente `TrackPlayer` se encarga de cuándo dibujar, de la barra de progreso y del clic para saltar. Esta es la parte de la onda:

```vue title="resources/js/components/modules/tracks/TrackPlayer.vue"
<template>
  <div
    v-if="propsTrack?.waveform"
    ref="waveformElement"
    v-width-percentage="onPercentage"
    v-resize="drawWaveform"
    class="track-player-progress-container w-full cursor-pointer d-flex align-center"
  >
    <div
      class="track-player-progress"
      :style="{ width: `${ width }px` }"
    />
  </div>
  <div v-else class="flex flex-column justify-center align-center p-2">
    <app-icon size="x-large" icon="mdi-waveform" class="animate__animated animate__rubberBand animate__infinite" />
    <span class="text-caption text-grey" v-text="Translator.tl('generating_waveform')+'...'" />
  </div>
</template>
```

Si el track todavía no tiene onda (el job no ha terminado), mostramos un icono animado con el texto *"Generando forma de onda..."*.

### Cuándo dibujar

```js title="resources/js/components/modules/tracks/TrackPlayer.vue"
const drawWaveform = () => {
    const json = propsTrack.value?.waveform;

    if (! json) return;

    nextTick(() => WaveDrawer.draw(json, waveformElement.value, propsTrack.value.color ?? DEFAULT_COLOR));
}

watch(propsTrack, () => {
    addTrackToPlayer();
    setWidth();
    setTimeout(drawWaveform, 100);
}, { immediate: true, deep: true });
```

- **Al montar el componente o cambiar de track**, con el `watch`. El pequeño retraso deja que el contenedor tenga ya su ancho final antes de medirlo.
- **Al cambiar el tamaño de la ventana**, con `v-resize`, una directiva de Vuetify que llama a la función en cada `resize`.
- **Dentro de `nextTick()`**, para que el `div` ya exista en el DOM cuando `WaveDrawer` lo mida.

Cada track tiene su propio color, que también es el de la onda.

### La barra de progreso

La barra de progreso no se dibuja dentro del SVG: es un `div` semitransparente encima de la onda, cuyo ancho sigue al porcentaje reproducido.

```js title="resources/js/components/modules/tracks/TrackPlayer.vue"
const setWidth = () => {
    if (isActive.value && waveformElement.value) {
        width.value = progressPercent.value * waveformElement.value.offsetWidth;
    } else {
        width.value = 0;
    }
}

watch(progressPercent, () => {
    if (! waveformElement.value) return;

    setWidth();
});
```

```css title="resources/js/components/modules/tracks/TrackPlayer.vue"
.track-player .waveform {
    fill-opacity: 0.7;
    transition: fill-opacity .4s ease;
}

.track-player.playing .waveform {
    fill-opacity: 1;
}

.track-player-progress-container {
    position: relative;
    height: 60px;
}

.track-player .track-player-progress {
    height: 60px;
    background: rgba(0, 0, 0, 0.1);
    position: absolute;
    transition: all .20s;
}
```

`progressPercent` viene del store del reproductor (un store de Pinia sobre [AmplitudeJS](https://521dimensions.com/open-source/amplitudejs)), que lo actualiza en cada `timeupdate` del audio.
`isActive` comprueba que este track es el que está sonando, porque en una lista hay muchos reproductores y solo uno avanza.

> [!TIP]
> Mover un `div` es mucho más barato que redibujar la onda en cada `timeupdate`, que se dispara varias veces por segundo. La onda se dibuja una vez y la barra solo cambia de ancho.

Mientras suena, la onda pasa de una opacidad de 0,7 a 1, con una transición. Es un detalle pequeño, pero ayuda a ver qué pista está sonando.

### Clic para saltar

Para saltar a un punto de la pista, una directiva calcula en qué porcentaje del ancho se ha hecho clic:

```js title="resources/js/directives/widthPercentage.js"
export default {
    beforeMount: (el, binding) => {
        /**
         * Where the click fell, as a percentage of the width of the element. The click and the element are both
         * measured from the viewport, so the result does not depend on how far the page is scrolled.
         */
        el.getWidthPercent = event => {
            let offset = el.getBoundingClientRect();
            let x = event.clientX - offset.left;
            let percent = x / parseFloat(el.offsetWidth);

            let expression = binding.value;

            if (typeof expression === 'function') {
                expression(percent * 100); // execute given expression
            }
        }
        el.addEventListener('click', el.getWidthPercent)
    },
    unmounted: el => {
        el.removeEventListener('click', el.getWidthPercent)
    },
}
```

> [!WARNING]
> Usa `clientX` y no `pageX`. `getBoundingClientRect()` mide desde la ventana y `clientX` también, pero `pageX` mide desde el documento.
> Si la página tiene scroll horizontal, con `pageX` el clic cae más a la derecha de donde ha pinchado el usuario: con 50 píxeles de scroll, un clic en el centro de una onda de 400 píxeles saltaría al 62,5 % en lugar de al 50 %.

Y el componente se lo pasa al reproductor:

```js title="resources/js/components/modules/tracks/TrackPlayer.vue"
const onPercentage = (percent) => setPlayedPercentage(index.value, percent);
```

> [!NOTE]
> Como la onda siempre ocupa todo el ancho, el porcentaje del clic es directamente el porcentaje de la pista. No hace falta ninguna conversión entre píxeles de la onda y segundos.

## Tests {#tests}

Los tests de `AudioHelper` ejecutan audiowaveform de verdad, con unos audios de prueba que genera el propio proyecto con FFmpeg.
Así el repositorio no contiene música de nadie, y como los audios son siempre iguales, sabemos exactamente qué resultado esperar:

```bash title="tests/mocks/generate-audio.sh"
# The expression of a clip: its tempo in BPM and the root frequency of its chord in Hz.
expression() {
    local period="60/$1" root=$2

    echo "0.7*sin(2*PI*55*t)*exp(-12*mod(t,$period))" \
        "+ 0.12*(2*random(0)-1)*exp(-90*mod(t+$period/2,$period))" \
        "+ 0.05*(sin(2*PI*$root*t)+sin(2*PI*$root*1.26*t)+sin(2*PI*$root*1.5*t))"
}
```

Cada audio es un bombo en cada tiempo, un charles entre tiempos y un acorde suave, a un tempo conocido. Las imágenes de este tutorial están hechas con un audio generado de la misma forma.

Con un audio de 20 segundos, la fórmula de antes dice que la onda tiene `ceil(20 × 44100 / 8192) = 108` puntos y 216 valores:

```php title="tests/Unit/Helpers/AudioHelperTest.php"
/**
 * The mocks come from tests/mocks/generate-audio.sh: the beat lasts 20 seconds at 120 BPM and the sample 8 seconds at
 * 95 BPM. With 8192 samples per pixel at 44.1 kHz, a waveform is ceil(seconds × 44100 / 8192) pixels long, and holds a
 * minimum and a maximum for each one.
 */
class AudioHelperTest extends TestCase
{
    #[Test]
    public function it_can_process_an_audio_file_correctly()
    {
        $waveformData = $this->helper->getWaveformData($this->file);

        $this->assertEquals(2, $waveformData['version']);
        $this->assertEquals(1, $waveformData['channels']);
        $this->assertEquals(44100, $waveformData['sample_rate']);
        $this->assertEquals(8192, $waveformData['samples_per_pixel']);
        $this->assertEquals(8, $waveformData['bits']);
        $this->assertEquals(108, $waveformData['length']);
        $this->assertCount(216, $waveformData['data']);
    }
}
```

Para que estos tests funcionen en GitHub Actions, el workflow instala audiowaveform. En lugar de depender del PPA, descarga el `.deb` de las releases de GitHub y comprueba su checksum:

```yaml title=".github/workflows/ci.yml"
# audiowaveform has no Ubuntu package. Its Debian 13 build runs on Ubuntu 24.04, and GitHub hosts it, so CI
# does not depend on Launchpad being up. The checksum pins the exact file.
- name: Install Server Software
  env:
    AUDIOWAVEFORM_DEB: https://github.com/bbc/audiowaveform/releases/download/1.10.2/audiowaveform_1.10.2-1-13_amd64.deb
    AUDIOWAVEFORM_SHA256: 4208706c6ae5ffb5761dddf8294eaf648c7ba9fb4d6fc6ec9f021d1bb109ea6e
  run: |
    sudo rm -f /var/lib/man-db/auto-update
    curl -fsSL --retry 3 -o /tmp/audiowaveform.deb "$AUDIOWAVEFORM_DEB"
    echo "$AUDIOWAVEFORM_SHA256  /tmp/audiowaveform.deb" | sha256sum -c -
    sudo apt-get update
    sudo apt-get install -y --no-install-recommends /tmp/audiowaveform.deb ffmpeg bpm-tools libsox-fmt-all soundstretch id3v2
    audiowaveform --version
```

> [!TIP]
> Los tests del job (`ProcessAudioJob`) no ejecutan audiowaveform: sustituyen `FileService` por un mock. Así cada test comprueba una sola cosa y los lentos son solo los que tienen que serlo.

## Mejoras posibles {#improvements}

- **Menos puntos en pantallas estrechas.** Una canción de 3 minutos tiene 970 puntos, y en un móvil la onda puede medir 300 píxeles. `waveform-data` puede reducirlos antes de dibujar con `resample()`.
  Solo sirve para reducir: si pides más puntos de los que hay, lanza un error.

```js
let waveform = WaveformData.create(json);

if (width < waveform.length) {
    waveform = waveform.resample({ width });
}
```

- **La onda en formato binario.** Con `-o fichero.dat` audiowaveform genera un formato binario que ocupa menos que el JSON, y `WaveformData.create()` también lo lee si le pasas un `ArrayBuffer`. A cambio, ya no se puede guardar en una columna JSON ni leer a simple vista.

## Código {#code}

**app/Jobs/ProcessAudioJob.php**

```php title="app/Jobs/ProcessAudioJob.php"
<?php

namespace App\Jobs;

use App\Facades\Logger;
use App\Models\File;
use App\Models\Track;
use App\Services\FileService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private File $file;

    /**
     * Create a new job instance.
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(FileService $fileService): void
    {
        if (! $fileService->isAudio($this->file)) { // Only audios will be processed
            return;
        }

        if (! Storage::exists($this->file->path)) {
            Logger::exception(new FileNotFoundException('Audio Not Found for Processing. Deleting'), $this->file->toArray());
            $this->file->delete();

            return;
        }

        if ($this->file->fileable_type === Track::class) {
            /** @var Track $track */
            $track = $this->file->fileable;

            $track->forceFill([
                'waveform' => $fileService->getWaveform($this->file),
                'duration' => $fileService->getDuration($this->file),
                'bpm' => $track->bpm ?? $fileService->getBPM($this->file),
            ])->save();
        }
    }
}
```

> [!NOTE]
> `FileService::getWaveform()` solo llama a `AudioHelper::getWaveformData()` y captura cualquier excepción. `Logger` es una facade propia de Talent Battles: puedes cambiarla por `report($exception)` o `Log::error()`.

**app/Helpers/AudioHelper.php** (la parte de la onda)

```php title="app/Helpers/AudioHelper.php"
<?php

namespace App\Helpers;

use App\Facades\Logger;
use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class AudioHelper
{
    public const FORMAT_DATA = 'dat';

    public const FORMAT_JSON = 'json';

    public const FORMAT_PNG = 'png';

    private Process $process;

    public string $output;

    /**
     * It generates a file with waveform data.
     * After generating the file, it will read its content in $waveformData to return it.
     * Finally, it will delete generated file
     *
     * @return array<mixed>|string|null
     */
    public function getWaveformData(File $file, string $format = self::FORMAT_JSON): array|string|null
    {
        $filename = pathinfo($file->path, PATHINFO_FILENAME);
        $extension = pathinfo($file->path, PATHINFO_EXTENSION);
        $tmpFilePath = "$filename.$extension";

        $fullPath = $this->createTempFile($file->path);

        $directory = pathinfo($fullPath, PATHINFO_DIRNAME);
        $destiny = "$directory/$filename.$format";

        $this->process = resolve(Process::class, [
            'command' => ['audiowaveform', '-i', $fullPath, '-o', $destiny, '-b', '8', '-z', '8192'],
        ]);

        try {
            $this->process->run();

            $this->output = $this->getProcessOutput();

            $jsonFilePath = Str::replace(".$extension", ".$format", $tmpFilePath);

            if (! Storage::disk('tmp')->exists($jsonFilePath)) {
                Logger::error("File not found $jsonFilePath", ['output' => $this->output]);

                return null;
            }

            $waveformData = Storage::disk('tmp')->get($jsonFilePath);

            Storage::disk('tmp')->delete($jsonFilePath);
            Storage::disk('tmp')->delete($tmpFilePath);

            if ($format === self::FORMAT_JSON) {
                return (array) json_decode($waveformData);
            }

            return $waveformData;
        } catch (Exception $exception) {
            Logger::exception($exception);

            return null;
        }
    }

    /**
     * Creates a temporal file and returns its full path
     */
    private function createTempFile(string $path): string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $tmpFilePath = "$filename.$extension";

        Storage::disk('tmp')
            ->put($tmpFilePath, Storage::get($path));

        return Storage::disk('tmp')->path($tmpFilePath);
    }

    private function getProcessOutput(): string
    {
        $this->output = $this->process->getOutput();

        if (empty($this->output)) {
            $this->output = $this->process->getErrorOutput();
        }

        return $this->output;
    }
}
```

**resources/js/objects/WaveDrawer.js** está completo en la sección [Dibujar la onda con D3](#draw).

¡Y ya está!

Ahora cada audio que se sube tiene su onda en unos segundos, el navegador solo descarga unos pocos KB para pintarla, y quien escucha puede ver la pista entera y saltar a cualquier punto con un clic.
