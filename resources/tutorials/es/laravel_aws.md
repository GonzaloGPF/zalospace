# Despliegues con Laravel y AWS

---

- [Introducción](#intro)
- [Requisitos](#requirements)
- [Setup](#setup)
- [Run Tests](#execute_tests)
  - [Código](#execute_tests_code)
- [Build and Upload assets](#build_and_upload_assets)
  - [Código](#build_and_upload_assets_code)
- [Deploy to Prod](#deploy_to_prod)
  - [Código](#deploy_to_prod_code)
- [Download Assets](#download_assets)
- [Forge Deploy Script](#deploy_script)

<a name="intro"></a>

## Introducción

Desarrollar con Laravel es muy cómodo, sin embargo, desplegar una aplicación aveces no es tan sencillo.
En este tutorial voy a detallar cómo realizar despliegues de aplicaciones Laravel de la manera más rápida, barata y sencilla posible.

Implementar un sistema de integración continua simplifica mucho el mantenimiento y desarrollo de una aplicación. Al automatizar
los despliegues te ahorrarás mucho tiempo. Esto te permitirá ganar agilidad para añadir nuevas funcionalidades y corregir bugs.

### El porqué de este tutorial

Si tienes una cuenta de AWS y Laravel Forge, la integración continua es prácticamente automática.
Basta con configurar Laravel Forge conectando tu cuenta de AWS y verás como tu servidor EC2
se aprovisionará con todo lo necesario para que tu aplicación Laravel esté disponible y funcional lo antes posible.

Forge instalará php, nginx, base de datos, creará usuarios, gestionará los permisos, el repo de git, etc. La verdad es que funciona muy bien.
Además, te permitirá **activar** despliegues automáticos y te ofrecerá la posibilidad de modificar el **script de despliegue** desde su panel de administración.

> {warning} El **script de despliegue** también realizará la compilación del front con `yarn build`.

Aquí es donde está el problema, mientras compila, tu aplicación estará unos minutos 'actualizando'.

Podemos reducir el tiempo de actualización optando por una máquina más potente (más RAM y procesador).
También podemos conseguir despliegues con zero-downtime si combinamos [Laravel Forge](https://forge.laravel.com/) con [Laravel Envoyer](https://envoyer.io/).

Estas opciones son válidas, pero nos supondrán mayor coste.

> {info} En este tutorial explicaré cómo conseguir despliegues con zero-downtime sin aumentar costes

El truco será:

- Utilizar las [GitHub Actions](https://github.com/features/actions) para realizar la compilación.
- Subir los archivos compilados a S3
- Modificar el script de despliegue de Laravel Forge para descargar los archivos compilados de S3

Al compilar los ficheros desde las GitHub Actions, liberaremos al servidor de EC2 de esta tarea y solo tendrá que descargarlos del S3,
lo cual es una acción mucho más rápida que `yarn build`.

<a name="requirements"></a>

## Requisitos

Antes de empezar, es importante saber qué tecnologías y herramientas utilizaremos para realizar la integración continua.

- [Laravel](https://laravel.com/): Una aplicación Laravel. En este tutorial será una aplicación monolítica.
- [GitHub](https://github.com/): Tener nuestra aplicación alojada en un repositorio GitHub. Utilizaremos las [GitHub Actions](https://github.com/features/actions) para la Integración Continua.
- [Laravel Forge](https://forge.laravel.com/): Deberemos contar con una cuenta de Laravel Forge conectada con nuestra cuenta de AWS y GitHub.
- [AWS](https://aws.amazon.com/es/): Contaremos con la nube de AWS para nuestra infraestructura.
  - [EC2](https://aws.amazon.com/es/ec2/): Aquí es dónde subiremos el código de nuestra app que tenemos en GitHub.
  - [S3](https://aws.amazon.com/es/s3/): Para el almacenamiento temporal del front compilado.
- [Docker](https://www.docker.com/): Conocimientos básicos de Docker para crear los Workflows.

<a name="setup"></a>

## Setup

Como hemos comentado, utilizaremos las GitHub Actions para implementar la integración continua.

Para ello, deberemos crear en la raíz del proyecto un directorio `.github/workflows`.

Dentro de este directorio crearemos los siguientes ficheros:

![workflows](/images/tutorials/workflows.png)

GitHub detectará los workflows y los procesará automáticamente.

Aquí escribiremos el código necesario para automatizar nuestros despliegues.

> {info} Los workflows son contenedores Docker, por lo que podemos crear cualquier tipo de entorno

<a name="execute_tests"></a>

## Run Tests

El primer workflow es el más sencillo, se trata de crear un setup típico de Laravel para ejecutar nuestros tests.

Lo primero que hacemos es preparar un entorno ubuntu con una base de datos mysql.

Modificaremos la sección **env** para
adaptarla a nuestra configuración, asegurándonos de que la BBDD que vamos a crear tendrá las credenciales de nuestro **PhpUnit**.

```yaml
name: Run Tests

on: [push]

jobs:
  tests:
    name: Tests
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_USER: my_user
          MYSQL_PASSWORD: password
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
```

> {info} Este Workflow se ejecutará por GitHub siempre que se haga un push

Ahora, escribiremos los pasos con la sección `steps`.

```yaml
steps:
  - uses: actions/checkout@v4
  - name: Verify MySQL connection
    run: |
      mysql --version
      sudo apt-get install -y mysql-client
      mysql --host 127.0.0.1 --port ${{ job.services.mysql.ports['3306'] }} -umy_user -ppassword -e "SHOW DATABASES"

  - name: Install Server Software
    run: sudo apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2 software-properties-common

  - name: Setup PHP
    uses: shivammathur/setup-php@v2
    with:
      php-version: '8.2'
      extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv, redis, memcache
      coverage: none
```

Los primeros pasos son 'Verify MySQL connection', 'Install Server Software' y 'Setup PHP', encargados de instalar todo el software necesario para nuestros tests.
Aquí es donde añadiremos cualquier dependencia extra que necesite nuestro sistema, si fuese necesario.

Seguidamente, añadiremos otro paso más:

```yaml
- name: Prepare env file
  run: cp .env.ci .env
```

El paso 'Prepare env file' es importante, ya que es el que va a crear el fichero **.env** de este workflow. En este caso, estamos utilizando un **.env.ci** existente en la raíz del proyecto.

Ahora ya podemos instalar las dependencias de Composer con:

```yaml
- name: Run composer install
  run: composer install -n --prefer-dist
```

Con esto, tendremos el entorno Docker listo para lanzar nuestra aplicación Laravel y ejecutar los tests.

```yaml
- name: Prepare Laravel Application
  run: php artisan key:generate

- name: Run tests
  env:
    MYSQL_DATABASE: testing
    DB_USERNAME: my_user
    DB_PASSWORD: password
    DB_PORT: ${{ job.services.mysql.ports[3306] }}
  run: php vendor/bin/phpunit
```

Si los tests se completan sin errores, el workflow finalizará satisfactoriamente.

## Run Tests - Código

<a name="execute_tests_code"></a>
**1_run_tests.yml**

```yaml
name: Run Tests

on: [push]

jobs:
  tests:
    name: Tests
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_USER: my_user
          MYSQL_PASSWORD: password
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    steps:
      - uses: actions/checkout@v4
      - name: Verify MySQL connection
        run: |
          mysql --version
          sudo apt-get install -y mysql-client
          mysql --host 127.0.0.1 --port ${{ job.services.mysql.ports['3306'] }} -umy_user -ppassword -e "SHOW DATABASES"

      - name: Install Server Software
        run: sudo apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2 software-properties-common

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv, redis, memcache
          coverage: none

      - name: Prepare env file
        run: cp .env.ci .env

      - name: Run composer install
        run: composer install -n --prefer-dist

      - name: Prepare Laravel Application
        run: php artisan key:generate

      - name: Run tests
        env:
          MYSQL_DATABASE: testing
          DB_USERNAME: my_user
          DB_PASSWORD: password
          DB_PORT: ${{ job.services.mysql.ports[3306] }}
        run: php vendor/bin/phpunit
```

Si todo va bien, el workflow se completará satisfactoriamente, de lo contrario, nos mostrará un mensaje de error.

> {success} Nuestro código ejecutará los tests por cada push

<a name="build_and_upload_assets"></a>

## Build and Upload assets

Este workflows es el más tricky. Aquí es donde realizaremos la compilación del front para poder eliminarla del script de despliegue de Laravel Forge.

Lo primero que vamos a hacer es indicar que este workflow se ejecutará después del workflow anterior 'Run Tests'.

También indicaremos que lo hará únicamente en master y, si y solo si, el workflow anterior finalizó satisfactoriamente.

```yaml
name: Build and Upload Assets

on:
  workflow_run:
    workflows: ['Run Tests']
    branches: [master]
    types: [completed]

jobs:
  build-and-upload:
    runs-on: ubuntu-latest
    if: ${{ github.event.workflow_run.conclusion == 'success' }}
```

> {info} La opción `workflow_run` en la sección `on` nos permite especificar cuándo lanzar este workflow.

Los primeros pasos a ejecutar, serán los necesarios para compilar el front con `yarn build`.

Tras ejecutar `yarn build`, [Vite](https://vitejs.dev/) ubicará todos los ficheros dentro de `public/build`.

Por esta razón, finalizaremos el proceso comprimiendo todo el directorio `public/build` en **build.zip**.

```yaml
on:
  steps:
    - uses: actions/checkout@v4
    - uses: actions/setup-node@v4
      with:
        node-version: '20'
        cache: 'yarn'
    - run: yarn
    - run: yarn build
    - run: zip -r build.zip public/build
```

> {info} En el fichero **build.zip** tendremos todos nuestros ficheros front compilados

Ahora, añadiremos un pequeño paso extra de optimización.

Cachearemos el resultado de la compilación para evitar 'recompilar' siempre que no sea necesario.

```yaml
- name: Cache Build
  uses: actions/cache@v4
  with:
    path: build.zip
    key: ${{ runner.os }}-build-${{ hashFiles('**/package-lock.json') }}-${{ hashFiles('public/build/manifest.json') }}
```

> {info} Esto agilizará nuestra integración continua al evitar procesamiento innecesario

Finalmente, subiremos el fichero **build.zip** a un **bucket** en S3 de AWS.

> {warning} Previamente deberás de haber creado un **bucket** de S3

```yaml
- name: Setup AWS CLI
  uses: aws-actions/configure-aws-credentials@v4
  with:
    aws-access-key-id: '${{ secrets.AWS_KEY_ID }}'
    aws-secret-access-key: '${{ secrets.AWS_SECRET_ACCESS_KEY }}'
    aws-region: 'eu-south-2'
- name: Sync files to S3 bucket
  run: |
    aws s3 cp build.zip s3://my_bucket.com/builds/
```

Sustituye 'my_bucket' por el nombre de tu bucket de S3.

> {info} Deberás tener una cuenta de S3 [configurada](https://laravel-news.com/using-aws-s3-for-laravel-storage). No olvides crear un usuario IAM con permisos **S3FullAccess**

## Build and Upload assets - Código

<a name="build_and_upload_assets_code"></a>
**2_build_and_upload_assets.yml**

```yaml
name: Build and Upload Assets

on:
  workflow_run:
    workflows: ['Run Tests']
    branches: [master]
    types: [completed]

jobs:
  build-and-upload:
    runs-on: ubuntu-latest
    if: ${{ github.event.workflow_run.conclusion == 'success' }}
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'yarn'
      - run: yarn
      - run: yarn build
      - run: zip -r build.zip public/build
      - name: Cache Build
        uses: actions/cache@v4
        with:
          path: build.zip
          key: ${{ runner.os }}-build-${{ hashFiles('**/package-lock.json') }}-${{ hashFiles('public/build/manifest.json') }}
      - name: Setup AWS CLI
        uses: aws-actions/configure-aws-credentials@v4
        with:
          aws-access-key-id: '${{ secrets.AWS_KEY_ID }}'
          aws-secret-access-key: '${{ secrets.AWS_SECRET_ACCESS_KEY }}'
          aws-region: 'eu-south-2'
      - name: Sync files to S3 bucket
        run: |
          aws s3 cp build.zip s3://my_bucket.com/builds/
```

<a name="deploy_to_prod"></a>

## Deploy to Prod

Este workflows es el más sencillo. Simplemente, solicitaremos a Laravel Forge que realice el despliegue utilizando `laravel/forge-cli`.

Al igual que en el workflow anterior, lo primero que vamos a hacer es indicar que este workflow se ejecutará después del workflow anterior 'Build and Upload Assets'.

También indicaremos que lo hará únicamente en master y, si y solo si, el workflow anterior finalizó satisfactoriamente.

```yaml
name: Deploy to Prod

on:
  workflow_run:
    workflows: ['Build and Upload Assets']
    branches: [master]
    types: [completed]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: ${{ github.event.workflow_run.conclusion == 'success' }}
```

Los primeros pasos que vamos a definir serán para preparar el entorno con lo necesario para `laravel/forge-cli`.

```yaml
steps:
  - name: Checkout code
    uses: actions/checkout@v4

  - name: Setup SSH
    uses: webfactory/ssh-agent@v0.9.0
    with:
      ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}

  - name: Setup PHP
    uses: shivammathur/setup-php@v2
    with:
      php-version: 8.1
      tools: composer:v2
      coverage: none
```

> {warning} Deberemos crear claves ssh, para comunicar GitHub con Laravel Forge.

Añadiremos la clave pública en la sección de **SSH Keys** de nuestro servidor de Forge
![Forge SSH](/images/tutorials/forge_ssh.png)

La clave privada la añadiremos en los **secrets** de GitHub Actions.
![GitHub Actions Secrets](/images/tutorials/github_actions_secrets.png)

Finalmente, escribiremos los pasos para realizar el despliegue con `laravel/forge-cli`

Necesitaremos definir una variable extra con un Api Token de Forge, para que `laravel/forge-cli` se autentique a la hora de ordenar el despliegue.

![Forge Api Tokens](/images/tutorials/forge_api_tokens.png)

Puedes ponerle cualquier nombre descriptivo, como por ejemplo 'GitHub'.

Al crearlo, Forge te mostrará el token **una única vez**.

Guárdalo en un secret llamado `FORGE_API_TOKEN` en el repositorio de GitHub.

```yaml
- name: Require Forge CLI
  run: composer global require laravel/forge-cli

- name: Deploy Site
  run: |
    forge server:switch my_app
    forge deploy my_app.com || echo 'Completed!'
  env:
    FORGE_API_TOKEN: ${{ secrets.FORGE_API_TOKEN }}
```

> {info} Sustituye el valor de 'my_app' por el nombre de tu app en Laravel Forge

<a name="deploy_to_prod_code"></a>

## Deploy to Prod - Código

```yaml
name: Deploy to Prod

on:
  workflow_run:
    workflows: ['Build and Upload Assets']
    branches: [master]
    types: [completed]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: ${{ github.event.workflow_run.conclusion == 'success' }}
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup SSH
        uses: webfactory/ssh-agent@v0.9.0
        with:
          ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
          tools: composer:v2
          coverage: none

      - name: Require Forge CLI
        run: composer global require laravel/forge-cli

      - name: Deploy Site
        run: |
          forge server:switch talentbattles
          forge deploy talentbattles.com || echo 'Completed!'
        env:
          FORGE_API_TOKEN: ${{ secrets.FORGE_API_TOKEN }}
```

<a name="download_assets"></a>

## Download Assets

Antes de modificar el script de despliegue de Forge, crearemos un comando custom en Laravel para poder llamarlo desde el script de despliegue.

Este comando se encargará de descargar el fichero **build.zip** que habremos subido al S3, lo descomprimirá y ubicará todos los ficheros en `public/build`

```bash
php artisan make:command DownloadAssets
```

Ahora, escribiremos la implementación.

Haciendo uso de **MountManager**, podremos copiar ficheros del disco S3 al disco local.

Luego descomprimiremos los ficheros con **ZipArchive** y los moveremos a `public/build`

> {warning} Hay que tener en cuenta que la ruta `public/build` del disco local es `storage/app/public/build`

```php
class DownloadAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download assets and unzip';
    private string $sourcePath;
    private \Illuminate\Filesystem\FilesystemAdapter|\Illuminate\Contracts\Filesystem\Filesystem $localDisk;
    private string $tmpZipPath;
    private string $tmpZipPathExtraction;
    private MountManager $mountManager;
    private bool $hasError;

    public function __construct()
    {
        parent::__construct();
        $this->hasError = false;
        $this->sourcePath = 'builds/build.zip';
        $this->localDisk = Storage::disk('local');
        $this->tmpZipPath = File::PATH_TMP.'/build.zip';
        $this->tmpZipPathExtraction = 'builds';
        $this->mountManager = new MountManager([
            's3' => \Storage::disk('s3')->getDriver(),
            'local' => \Storage::disk('local')->getDriver(),
        ]);
    }

    /**
     * Execute the console command.
     */
    public function handle(ZipArchive $zipArchive): void
    {
        $this->info("Downloading and setting up assets...");

        try {
            $this->mountManager->copy("s3://$this->sourcePath", "local://$this->tmpZipPath");
            $this->info("Assets downloaded from s3://$this->sourcePath");
        } catch (FilesystemException $error) {
            $this->error($error->getMessage());
            $this->hasError = true;
        }
        $zipPath = $this->localDisk->path($this->tmpZipPath);
        $zipPathDestiny = $this->localDisk->path($this->tmpZipPathExtraction);

        $zipArchive->open($zipPath);

        if ($zipArchive->extractTo($zipPathDestiny)) {
            $zipArchive->close();
            $this->info("Assets extracted to $zipPathDestiny");
        } else {
            $this->error("Error extracting assets");
        }

        try {
            Storage::disk('public')->deleteDirectory('build');
            $this->mountManager->move("local://builds/public/build", "local://public/build");
            $this->info('Assets moved to storage/public');
        } catch (FilesystemException $error) {
            $this->error($error->getMessage());
            $this->hasError = true;
        }

        $this->info('Cleaning temporal directories...');

        $this->localDisk->delete($this->tmpZipPath);
        $this->localDisk->deleteDirectory($this->tmpZipPathExtraction);

        if (! $this->hasError) {
            $this->info('Assets successfully published in public/build folder!');
        }
    }
}
```

Con esto, tendremos todos los ficheros descomprimidos en `storage/app/public/build`.

El último paso será modificar el script de despliegue de Forge para mover los ficheros del `storage` al directo `public`.

<a name="deploy_script"></a>

## Forge Deploy Script

El último paso que deberemos hacer es modificar el script de despliegue de Laravel Forge, eliminando el `yarn build`.

En su lugar, llamaremos al comando que creamos arriba para descargar los ficheros de nuestro S3.

También escribiremos un par de líneas para mover desde el bash los ficheros de `storage/app/public/build` a `public/build`.

**Forge deploy script**

```bash
cd /home/forge/my_app.com
git reset --hard
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
fi

$FORGE_COMPOSER clear

#yarn build // remove this!

rm -r public/build # remove any previous public/build directory

$FORGE_PHP artisan app:download-assets
#$FORGE_PHP artisan notify:new_deploy

mv -f storage/app/public/build public # move compiled files to public directory
```

¡Y ya está!

Ahora, cada push que hagamos a master, activará los workflows, se ejecutarán los tests,
se compilarán los assets y se subirán al S3, luego se descargarán y finalmente se ubicarán en el lugar adecuado para servir a los usuarios,
todo con un downtime muy reducido.
