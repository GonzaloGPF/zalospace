<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ config('company.contact.author') }}</title>
    <style>
        body {
            font-family: "Helvetica", "Arial", sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
        }

        h1 {
            font-size: 22px;
            color: #0e4178;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 14px;
            color: #0e4178;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin: 18px 0 8px;
        }

        h3 {
            font-size: 12px;
            margin: 12px 0 2px;
        }

        p {
            margin: 4px 0;
        }

        a {
            color: #0e4178;
            text-decoration: none;
        }

        blockquote {
            margin: 10px 0;
            padding: 6px 10px;
            background: #eff6ff;
            border-left: 3px solid #0e4178;
        }

        ul {
            margin: 4px 0;
            padding-left: 16px;
        }

        li {
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
        }
    </style>
</head>
<body>
{!! $content !!}
</body>
</html>
