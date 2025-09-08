<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Nadika Guest House</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="{{ asset('template/assets/img/kaiadmin/favicon.ico') }}"
      type="image/x-icon"
    />
  @include('layouts.style')
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
       <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="index.html" class="logo">
              <img
                src="{{ asset('template/assets/img/kaiadmin/logo_light.svg') }}"
                alt="navbar brand"
                class="navbar-brand"
                height="20"
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item">
                <a href="/dashboard">
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/booking">
                  <i class="fas fa-pen-square"></i>
                  <p>Booking</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/penginap">
                  <i class="fas fa-users"></i>
                  <p>Penginap</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/kamar">
                  <i class="fas fa-bed"></i>
                  <p>Kamar</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/rekap">
                  <i class="far fa-chart-bar"></i>
                  <p>Rekap</p>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      @include('layouts.header')
      
      @yield('dashboard')
      @yield('booking')
      @yield('penginap')
      @yield('kamar')
      @yield('rekap')

      </div>
    </div>
    @yield('script')
  </body>
</html>
