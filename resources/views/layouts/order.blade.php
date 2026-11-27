<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'MESA - Quản lý gọi món')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-slate-100 text-slate-800">

    <main>
        @yield('content')
    </main>

</body>
</html>