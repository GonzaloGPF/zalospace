@php
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => config('company.contact.author'),
        'url' => config('app.url'),
        'image' => url('images/profile_photo.jpg'),
        'jobTitle' => 'Senior Full-Stack Developer & Tech Lead',
        'description' => config('company.description'),
        'worksFor' => [
            '@type' => 'Organization',
            'name' => 'Learning Heroes',
        ],
        'knowsAbout' => ['Laravel', 'Vue.js', 'PHP', 'Inertia.js', 'Tailwind CSS', 'NestJS', 'AWS'],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Sevilla',
            'addressCountry' => 'ES',
        ],
        'email' => 'zalo.pabon@gmail.com',
        'sameAs' => [
            config('company.social.github'),
            config('company.social.linkedin'),
            config('company.social.laracasts'),
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
