![Tests](https://github.com/GonzaloGPF/zalospace/actions/workflows/1_run_tests.yml/badge.svg)

<img alt="ZaloSpace" src="./public/images/logo.png" width="600"/>

# ZaloSpace

Proyecto personal

## Tech Stack

- Fullstack framework: [Laravel 13](https://laravel.com/) on [PHP 8.3+](https://www.php.net/)
- Frontend: [Vue 3](https://vuejs.org/) + [Inertia 3](https://inertiajs.com/) + [Tailwind CSS 4](https://tailwindcss.com/)
- Build: [Vite 8](https://vite.dev/) (requires Node 20.19+)
- Tutorials: [LaRecipe 3](https://larecipe.saleem.dev/)
- Testing: [PHPUnit 12](https://phpunit.de/)
- Code style: [Pint](https://laravel.com/docs/pint), [ESLint](https://eslint.org/), [Stylelint](https://stylelint.io/) and [Prettier](https://prettier.io/)
- AI tooling: [Laravel Boost](https://laravel.com/docs/boost)
- Deployment: [Envoy](https://laravel.com/docs/envoy)

## Local development

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail yarn install
./vendor/bin/sail yarn dev
```

Run the tests with `./vendor/bin/sail artisan test`.
