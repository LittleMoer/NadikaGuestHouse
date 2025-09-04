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
              <li class="nav-item active">
                <a href="/dashboard">
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/tables">
                  <i class="fas fa-table"></i>
                  <p>Tables</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/forms">
                  <i class="fas fa-pen-square"></i>
                  <p>Forms</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="/charts">
                  <i class="far fa-chart-bar"></i>
                  <p>Charts</p>
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


      </div>
    </div>
    @yield('script')
  </body>
</html>
