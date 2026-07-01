    <link rel="stylesheet" href="{{ asset('template/assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/css/kaiadmin.min.css') }}" />
     <!-- Fonts and icons -->
    <script src="{{ asset('template/assets/js/plugin/webfont/webfont.min.js') }}"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["{{ asset('template/assets/css/fonts.min.css') }}"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>
    <style>
      /* Global form control border override */
      .form-control,
      .form-select,
      select.form-control,
      textarea.form-control,
      input.form-control,
      /* Flatpickr generated input (visible) */
      .flatpickr-input[readonly] {
        border-color: #000 !important;
        box-shadow: none;
      }
      .form-control:focus,
      .form-select:focus,
      .flatpickr-input[readonly]:focus {
        border-color: #000 !important;
        box-shadow: 0 0 0 .15rem rgba(0,0,0,.12);
      }

      /* Custom Responsive Design Overrides */
      html, body {
        max-width: 100vw;
        overflow-x: hidden;
      }
      .wrapper {
        overflow-x: hidden !important;
      }
      .main-panel {
        max-width: 100vw;
        overflow-x: hidden;
      }
      
      /* Mobile layout adjustments */
      @media (max-width: 991px) {
        .main-header {
          width: 100% !important;
          max-width: 100vw !important;
        }
        .main-header-logo {
          width: 100% !important;
        }
        .logo-header {
          width: 100% !important;
          display: flex !important;
          justify-content: space-between !important;
          align-items: center !important;
          padding: 0 15px !important;
        }
        .logo-header img.navbar-brand {
          max-width: 160px;
          height: auto;
        }
        /* Fix topbar navigation button alignment */
        .navbar-header {
          width: 100% !important;
        }
      }

      /* Screen height adjustments for 1366x768 and smaller laptops */
      @media (max-height: 800px) {
        .dash-table-wrap {
          height: 54vh !important;
        }
      }

      /* Stack modal details list on mobile */
      @media (max-width: 576px) {
        #bookingDetailModal .modal-body dl {
          grid-template-columns: 1fr !important;
          row-gap: 8px !important;
        }
        #bookingDetailModal .modal-body dt {
          margin-top: 4px;
          font-size: 0.75rem;
          color: #888;
        }
        #bookingDetailModal .modal-body dd {
          font-weight: 600;
          font-size: 0.85rem;
        }
      }
    </style>