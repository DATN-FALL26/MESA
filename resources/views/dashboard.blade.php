@extends('layouts.auth')

@section('title', 'Dashboard | MESA')
@section('heading', 'Tổng quan hệ thống')

@section('content')
    @php
        $user = auth()->user();
        $currentRoles = $user->activeRoles()->orderBy('name')->get();
        $totalUsers = \App\Models\User::query()->count();
        $activeUsers = \App\Models\User::query()
            ->where('status', \App\Enums\UserStatus::ACTIVE->value)
            ->count();
        $totalRoles = \App\Models\Role::query()->count();
        $totalPermissions = \App\Models\Permission::query()->count();
        $recentActivities = \App\Models\AuditLog::query()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
        $activityUsers = \App\Models\User::query()
            ->whereIn('id', $recentActivities->pluck('user_id')->filter()->unique())
            ->pluck('full_name', 'id');
        $actionLabels = [
            'create' => 'Đã tạo',
            'update' => 'Đã cập nhật',
            'delete' => 'Đã xóa',
            'status_change' => 'Đã thay đổi trạng thái',
            'login' => 'Đã đăng nhập',
        ];
        $modules = [
            [
                'permission' => \App\Helpers\ConstantHelper::PERM_USERS_VIEW,
                'route' => 'users.index',
                'title' => 'Quản lý tài khoản',
                'description' => 'Theo dõi tài khoản, thông tin nhân sự và trạng thái hoạt động.',
                'icon' => 'users',
                'color' => 'indigo',
            ],
            [
                'permission' => \App\Helpers\ConstantHelper::PERM_ROLES_VIEW,
                'route' => 'roles.index',
                'title' => 'Quản lý vai trò',
                'description' => 'Tổ chức vai trò và kiểm soát quyền truy cập theo nhóm.',
                'icon' => 'roles',
                'color' => 'violet',
            ],
            [
                'permission' => \App\Helpers\ConstantHelper::PERM_PERMISSIONS_VIEW,
                'route' => 'permissions.index',
                'title' => 'Quản lý quyền',
                'description' => 'Tra cứu các quyền chức năng được cấu hình trong hệ thống.',
                'icon' => 'permissions',
                'color' => 'amber',
            ],
        ];
    @endphp

    <div class="space-y-8 sm:space-y-10">
        <section class="relative isolate overflow-hidden rounded-3xl bg-slate-950 px-5 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9 lg:px-10">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                <div class="absolute -right-16 -top-28 h-72 w-72 rounded-full bg-indigo-500/25 blur-3xl"></div>
                <div class="absolute -bottom-36 right-1/3 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/90 to-indigo-950/60"></div>
            </div>

            <div class="relative flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-5 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-xs font-medium text-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            Trung tâm quản trị MESA
                        </span>
                        <time class="text-xs font-medium text-slate-300" datetime="{{ now()->toIso8601String() }}">
                            {{ now()->locale('vi')->translatedFormat('l, d/m/Y · H:i') }}
                        </time>
                    </div>
                    <p class="mb-2 text-sm font-medium text-indigo-200">Chào mừng trở lại</p>
                    <h2 class="max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">
                        Xin chào, {{ $user->full_name }}
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Theo dõi tổng quan hệ thống và truy cập nhanh các công cụ quản trị của bạn.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-medium text-slate-400">Vai trò hiện tại</span>
                        @forelse ($currentRoles as $role)
                            <span class="rounded-lg border border-white/10 bg-white/10 px-2.5 py-1.5 text-xs font-medium text-white">
                                {{ $role->name }}
                            </span>
                        @empty
                            <span class="rounded-lg border border-white/10 bg-white/10 px-2.5 py-1.5 text-xs font-medium text-slate-300">
                                Chưa được gán vai trò
                            </span>
                        @endforelse
                    </div>
                </div>

                <div class="flex w-full items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur sm:w-auto sm:min-w-64">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-400/15 text-indigo-200 ring-1 ring-inset ring-indigo-300/20">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-400">Phiên làm việc</p>
                        <p class="mt-1 text-sm font-semibold text-white">Đã xác thực</p>
                    </div>
                    <span class="ml-auto h-2 w-2 rounded-full bg-emerald-400" aria-label="Đang hoạt động"></span>
                </div>
            </div>
        </section>

        <section aria-labelledby="overview-heading">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="overview-heading" class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Tổng quan hệ thống</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Các chỉ số tài khoản và phân quyền</p>
                </div>
                <p class="text-xs text-slate-400">Cập nhật {{ now()->format('H:i') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Tổng tài khoản', 'value' => $totalUsers, 'detail' => 'Tài khoản trong hệ thống', 'icon' => 'users', 'color' => 'indigo'],
                    ['label' => 'Tổng vai trò', 'value' => $totalRoles, 'detail' => 'Nhóm phân quyền', 'icon' => 'roles', 'color' => 'violet'],
                    ['label' => 'Tổng quyền', 'value' => $totalPermissions, 'detail' => 'Quyền chức năng', 'icon' => 'permissions', 'color' => 'amber'],
                    ['label' => 'Đang hoạt động', 'value' => $activeUsers, 'detail' => 'Tài khoản hoạt động', 'icon' => 'active', 'color' => 'emerald'],
                ] as $stat)
                    <article class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5 dark:bg-slate-900 dark:ring-slate-800">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">{{ number_format($stat['value']) }}</p>
                            </div>
                            <div @class([
                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition duration-200 group-hover:scale-105',
                                'bg-indigo-50 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-300' => $stat['color'] === 'indigo',
                                'bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-300' => $stat['color'] === 'violet',
                                'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300' => $stat['color'] === 'amber',
                                'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-300' => $stat['color'] === 'emerald',
                            ])>
                                @if ($stat['icon'] === 'users')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" /></svg>
                                @elseif ($stat['icon'] === 'roles')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.25c0 4.33 3.2 8.23 7.5 9.75 4.3-1.52 7.5-5.42 7.5-9.75V6L12 3Zm-3 9 2 2 4-4" /></svg>
                                @elseif ($stat['icon'] === 'permissions')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 1 1-5.7 1.29L3 13.59V18h4v-3h3v-3h2.41l1.3-1.3a3 3 0 0 1 2.04-5.45Z" /></svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                @endif
                            </div>
                        </div>
                        <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">{{ $stat['detail'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="modules-heading">
            <div class="mb-4">
                <h2 id="modules-heading" class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Truy cập nhanh</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Mở các khu vực quản trị theo quyền của bạn</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($modules as $module)
                    @if ($user->hasPermission($module['permission']))
                        <a href="{{ route($module['route']) }}" @class([
                            'group rounded-2xl bg-white p-5 shadow-sm ring-1 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-900/5 dark:bg-slate-900 dark:ring-slate-800',
                            'ring-slate-200/80 hover:ring-indigo-300 dark:hover:ring-indigo-400/40' => $module['color'] === 'indigo',
                            'ring-slate-200/80 hover:ring-violet-300 dark:hover:ring-violet-400/40' => $module['color'] === 'violet',
                            'ring-slate-200/80 hover:ring-amber-300 dark:hover:ring-amber-400/40' => $module['color'] === 'amber',
                        ])>
                            <div class="flex items-start justify-between gap-4">
                                <div @class([
                                    'flex h-11 w-11 items-center justify-center rounded-xl transition-colors group-hover:text-white',
                                    'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-300' => $module['color'] === 'indigo',
                                    'bg-violet-50 text-violet-600 group-hover:bg-violet-600 dark:bg-violet-400/10 dark:text-violet-300' => $module['color'] === 'violet',
                                    'bg-amber-50 text-amber-600 group-hover:bg-amber-600 dark:bg-amber-400/10 dark:text-amber-300' => $module['color'] === 'amber',
                                ])>
                                    @if ($module['icon'] === 'users')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" /></svg>
                                    @elseif ($module['icon'] === 'roles')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.25c0 4.33 3.2 8.23 7.5 9.75 4.3-1.52 7.5-5.42 7.5-9.75V6L12 3Zm-3 9 2 2 4-4" /></svg>
                                    @else
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 1 1-5.7 1.29L3 13.59V18h4v-3h3v-3h2.41l1.3-1.3a3 3 0 0 1 2.04-5.45Z" /></svg>
                                    @endif
                                </div>
                                <svg class="mt-1 h-5 w-5 text-slate-300 transition duration-200 group-hover:translate-x-1 group-hover:text-slate-500 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7M7 7h10v10" /></svg>
                            </div>
                            <h3 class="mt-5 font-semibold text-slate-900 dark:text-white">{{ $module['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $module['description'] }}</p>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 sm:p-6 dark:bg-slate-900 dark:ring-slate-800" aria-labelledby="activity-heading">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="activity-heading" class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Hoạt động hệ thống gần đây</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Các thay đổi được ghi nhận trong nhật ký kiểm toán</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                    Nhật ký hệ thống
                </span>
            </div>

            @if ($recentActivities->isNotEmpty())
                <ol class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($recentActivities as $activity)
                        <li class="flex gap-3 py-4 first:pt-0 last:pb-0 sm:gap-4">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between sm:gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                        {{ $actionLabels[$activity->action] ?? ucfirst(str_replace('_', ' ', $activity->action)) }}
                                        {{ str_replace('_', ' ', $activity->entity_type) }}
                                        @if ($activity->entity_id)
                                            <span class="text-slate-500">#{{ $activity->entity_id }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">
                                        Thực hiện bởi {{ $activityUsers[$activity->user_id] ?? 'Hệ thống' }}
                                    </p>
                                </div>
                                <time class="mt-2 block shrink-0 text-xs text-slate-400 sm:mt-0" datetime="{{ $activity->created_at?->toIso8601String() }}">
                                    {{ $activity->created_at?->diffForHumans() ?? 'Không rõ thời gian' }}
                                </time>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="rounded-xl border border-dashed border-slate-200 px-4 py-9 text-center dark:border-slate-700">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-300">Chưa có hoạt động được ghi nhận</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Nhật ký sẽ xuất hiện tại đây khi hệ thống ghi nhận thay đổi.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
