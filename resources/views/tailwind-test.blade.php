<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailwind Test</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center">

    <div class="bg-white p-10 rounded-2xl shadow-xl text-center">
        <h1 class="text-4xl font-bold text-blue-600">
            Tailwind hoạt động ✅
        </h1>

        <p class="mt-4 text-gray-600">
            Laravel + Blade + Tailwind đã kết nối thành công.
        </p>

        <button class="mt-6 bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl font-semibold">
            Test Button
        </button>
    </div>

</body>
</html>