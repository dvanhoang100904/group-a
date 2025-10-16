<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Tài liệu')</title>

    <!-- bootstrap icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    {{-- my css --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    
   <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    {{-- header --}}
    @include('layouts.header')

    <!-- Overlay for mobile -->
    <div class="overlay" id="overlay"></div>

    {{-- sidebar --}}
    @include('layouts.sidebar')

    {{-- main content --}}
    <main class="content" id="mainContent">
        <div class="container-fluid">
            @yield('content')
            {{-- footer --}}
            @include('layouts.footer')
        </div>
    </main>

    <!-- bootstrap js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>

    {{-- my js --}}
    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>
