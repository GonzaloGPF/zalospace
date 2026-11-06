@setup
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();

  $dir = env('DEPLOY_DIR');
  $host = env('DEPLOY_HOST');
  $user = env('DEPLOY_USER');
  $server = "$user@$host"
@endsetup

@servers(['main' => $server])

@story('deploy')
  info
  preflight
  site-down
  git
  backend
  frontend
  site-up
@endstory

@task('info')
  echo 'Deploying to {{ $server }} at {{ $dir }}'
@endtask

@task('preflight')
  cd {{ $dir }}

  php -r 'if (PHP_VERSION_ID < 80300) { echo "PHP 8.3+ required, found ".PHP_VERSION."\n"; exit(1); }'
  php -r 'foreach (["ctype", "dom", "fileinfo", "iconv", "mbstring", "openssl", "simplexml", "tokenizer"] as $extension) { if (! extension_loaded($extension)) { echo "Missing PHP extension: $extension\n"; exit(1); } }'
  node -e 'const [major, minor] = process.versions.node.split(".").map(Number); if (!((major === 20 && minor >= 19) || (major === 22 && minor >= 13) || major >= 24)) { console.error("Node 20.19+, 22.13+ or 24+ required, found " + process.version); process.exit(1); }'
  composer --version
  yarn --version
  git fetch origin main
@endtask

@task('site-down')
  cd {{ $dir }}

  php artisan down --render="errors::503" --retry=60
@endtask

@task('site-up')
  cd {{ $dir }}

  php artisan up
@endtask

@task('git')
  cd {{ $dir }}

  git reset --hard origin/main
@endtask

@task('backend')
  cd {{ $dir }}
  composer install --no-dev --no-interaction --no-plugins --no-progress --prefer-dist --optimize-autoloader

  php artisan optimize:clear
  #php artisan migrate --force
  php artisan vendor:publish --tag=larecipe-assets --force
  php artisan optimize
  php artisan queue:restart
@endtask

@task('frontend')
  cd {{ $dir }}
  yarn --frozen-lockfile --no-progress
  yarn build
@endtask

@error
  echo PHP_EOL."Deploy stopped at [{$task}]. If it was preflight, the site was not touched. Otherwise it is still in maintenance mode: fix the cause and run the deploy again.".PHP_EOL;
@enderror
