<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Juventud Iskali')</title>
  @if (request()->routeIs('landing'))
    @vite(['resources/css/Landing_Styles.css', 'resources/js/AppSistema.js'])
  @elseif (request()->routeIs('login', 'registro', 'recuperar_password', 'recuperar_password.reset'))
    @vite(['resources/css/Login_Styles.css'])
  @else
    @vite(['resources/js/ModuleShell.js'])
  @endif
</head>
<body class="@yield('body-class')">
  @if (Auth::check() && !request()->routeIs('landing', 'login', 'registro', 'recuperar_password', 'portal.donador'))
    @include('partials.sidebar')
  @endif
  @yield('content')
</body>
</html>
