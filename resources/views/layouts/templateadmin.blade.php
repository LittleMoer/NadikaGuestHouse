<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Nadika Guest House - Admin</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('template/assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />
    @include('layouts.style')
  </head>
  <body>
    <div class="wrapper">
      @include('layouts.sidebar')
      <div class="main-panel">
        @include('layouts.header')

        @yield('dashboard')
        @yield('booking')
        @yield('penginap')
        @yield('kamar')
        @yield('cafe')
        @yield('cafeorders')
      </div>
    </div>
    @include('layouts.scripts')
    @yield('script')
  </body>
  </html>
