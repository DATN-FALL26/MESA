@extends('layouts.auth')

@section('title', 'Chọn chi nhánh | MESA')
@section('heading', 'Chọn chi nhánh')

@section('content')
    <section class="mx-auto max-w-xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-lg font-semibold text-slate-900">Chọn chi nhánh làm việc</h1>
        <p class="mt-1 text-sm text-slate-600">Chọn một chi nhánh bạn được cấp quyền để tiếp tục.</p>

        <form method="GET" action="{{ $action ?? route('tables.index') }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
            <label for="branch_id" class="sr-only">Chi nhánh</label>
            <select id="branch_id" name="branch_id" required
                class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/20">
                <option value="">-- Chọn chi nhánh --</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            <button type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                Tiếp tục
            </button>
        </form>
    </section>
@endsection
