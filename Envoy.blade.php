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
  site-down
  git
  backend
  frontend
  site-up
@endstory

@task('info')
  echo 'Deploying to {{ $server }} at {{ $dir }}'
@endtask

@task('site-down')
  cd {{ $dir }}

  php artisan down
@endtask

@task('site-up')
  cd {{ $dir }}

  php artisan up
@endtask

@task('git')
  cd {{ $dir }}

  git reset --hard
  git pull origin main
@endtask

@task('backend')
  cd {{ $dir }}
  composer install --no-dev --no-interaction --no-plugins --no-progress --no-suggest --prefer-dist --optimize-autoloader

  php artisan optimize:clear
  #php artisan migrate --force
  php artisan larecipe:install
@endtask

@task('frontend')
  cd {{ $dir }}
  yarn --frozen-lockfile --no-progress
  yarn build
@endtask
