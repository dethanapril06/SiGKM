<!DOCTYPE html>
<html lang="id" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('template/assets') }}/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>{{ $title ?? 'SIGKM' }}</title>

    <meta name="description" content="Sistem Informasi Repository Gugus Kendali Mutu" />

    <link rel="icon" type="image/x-icon" href="{{ asset('template/assets/img/favicon/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/fonts/boxicons.css') }}" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('template/assets/vendor/css/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('template/assets/css/demo.css') }}" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    @stack('styles')

    <script src="{{ asset('template/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('template/assets/js/config.js') }}"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            @include('layouts.partials.sidebar')

            <div class="layout-page">

                @include('layouts.partials.navbar')

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>

                    @include('layouts.partials.footer')

                    <div class="content-backdrop fade"></div>
                </div>

            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script src="{{ asset('template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('template/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('template/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('template/assets/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('template/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('template/assets/js/main.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            function initSelect2() {
                $('.select2').each(function() {
                    const $select = $(this);
                    if ($select.hasClass("select2-hidden-accessible")) {
                        return;
                    }
                    $select.select2({
                        theme: 'bootstrap-5',
                        placeholder: $select.attr('placeholder') || $select.find('option:first').text() || '-- Pilih --',
                        allowClear: true,
                        width: '100%'
                    });
                });
            }

            initSelect2();

            // Re-init on BS modal show if select2 is inside modal
            $(document).on('shown.bs.modal', '.modal', function () {
                $(this).find('.select2').each(function() {
                    $(this).select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $(this).closest('.modal'),
                        placeholder: $(this).attr('placeholder') || $(this).find('option:first').text() || '-- Pilih --',
                        allowClear: true,
                        width: '100%'
                    });
                });
            });
        });

        document.addEventListener('submit', function(event) {
            const form = event.target;

            if (!form.matches('[data-confirm-form]')) {
                return;
            }

            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();

            const confirmTitle = form.dataset.confirmTitle || 'Apakah Anda yakin?';
            const confirmText = form.dataset.confirmText || 'Data akan diproses.';
            const confirmIcon = form.dataset.confirmIcon || 'warning';
            const confirmButtonText = form.dataset.confirmButtonText || 'Ya, lanjutkan';
            const cancelButtonText = form.dataset.cancelButtonText || 'Batal';

            if (typeof Swal === 'undefined') {
                if (confirm(confirmTitle)) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }

                return;
            }

            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: confirmIcon,
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                confirmButtonColor: form.dataset.confirmButtonColor || '#696cff',
                cancelButtonColor: '#8592a3',
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
