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
    </style>