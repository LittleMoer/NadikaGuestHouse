<div class="sidebar" data-background-color="dark">
  <div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
      <a href="/dashboard" class="logo">
        <img
          src="{{ asset('template/assets/img/kaiadmin/Nadika Guest House Syariah.svg') }}"
          alt="navbar brand"
          class="navbar-brand"
          height="25"
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
        <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
          <a href="/dashboard">
            <i class="fas fa-home"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <li class="nav-item {{ request()->is('booking') ? 'active' : '' }}">
          <a href="/booking">
            <i class="fas fa-pen-square"></i>
            <p>Booking</p>
          </a>
        </li>
        <li class="nav-item {{ request()->is('penginap') ? 'active' : '' }}">
          <a href="/penginap">
            <i class="fas fa-users"></i>
            <p>Penginap</p>
          </a>
        </li>
        <li class="nav-item {{ request()->is('kamar*') ? 'active' : '' }}">
          <a href="{{ route('kamar.index') }}">
            <i class="fas fa-bed"></i>
            <p>Kamar</p>
          </a>
        </li>
        <li class="nav-item {{ request()->is('cafe*') ? 'active' : '' }}">
          <a href="/cafe">
            <i class="fas fa-coffee"></i>
            <p>Cafe</p>
          </a>
        </li>
        <li class="nav-item {{ request()->is('cafeorders') ? 'active' : '' }}">
          <a href="/cafeorders">
            <i class="fas fa-receipt"></i>
            <p>Orders Cafe</p>
          </a>
        </li>
  @if(auth()->check() && auth()->user()->isOwner())
        <li class="nav-item {{ request()->is('rekap') ? 'active' : '' }}">
          <a href="/rekap">
            <i class="far fa-chart-bar"></i>
            <p>Rekap</p>
          </a>
        </li>
        <li class="nav-item {{ request()->is('users*') ? 'active' : '' }}">
          <a href="{{ route('users.index') }}">
            <i class="fas fa-user-cog"></i>
            <p>Manajemen Akun</p>
          </a>
        </li>
        @endif
      </ul>
    </div>
  </div>
</div>