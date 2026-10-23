<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sơ Đồ Bàn - I-PHO POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800" x-data="posTableManager()">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <!-- Header & Thống kê tổng quan -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                    <i class="fa-solid fa-utensils text-orange-500"></i> Quản Lý Sơ Đồ Bàn
                </h1>
                <p class="text-sm text-slate-500 mt-1">Hệ thống quản lý nhà hàng I-PHO</p>
            </div>

            <!-- Thống kê nhanh -->
            <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-slate-200 shadow-sm">
                <div class="px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-center">
                    <span class="text-xs font-medium text-emerald-600 block">Trống</span>
                    <span class="text-base font-bold text-emerald-700">
                        {{ $tables->flatten()->where('status', 'empty')->count() }}
                    </span>
                </div>
                <div class="px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-center">
                    <span class="text-xs font-medium text-rose-600 block">Đang dùng</span>
                    <span class="text-base font-bold text-rose-700">
                        {{ $tables->flatten()->where('status', 'occupied')->count() }}
                    </span>
                </div>
                <div class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-center">
                    <span class="text-xs font-medium text-amber-600 block">Cần dọn</span>
                    <span class="text-base font-bold text-amber-700">
                        {{ $tables->flatten()->where('status', 'cleaning')->count() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Thanh công cụ: Lọc Khu Vực + Tìm Kiếm + Nút Refresh API -->
        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_auto] items-center gap-4 mb-8 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
            
            <!-- Tabs Lọc Khu Vực -->
            <div class="flex min-w-0 items-center gap-1 overflow-x-auto pb-2 sm:pb-0">
                <button 
                    @click="activeZone = 'ALL'"
                    :class="activeZone === 'ALL' ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    class="px-4 py-2 rounded-xl text-sm transition-all whitespace-nowrap">
                    <i class="fa-solid fa-layer-group mr-1.5"></i>Tất cả khu vực
                </button>

                @foreach ($tables->keys() as $zoneName)
                    <button 
                        @click="activeZone = @js($zoneName)"
                        :class="activeZone === @js($zoneName) ? 'bg-orange-500 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                        class="px-4 py-2 rounded-xl text-sm transition-all whitespace-nowrap">
                        {{ $zoneName }}
                    </button>
                @endforeach
            </div>

            <!-- Ô Tìm Kiếm Bàn & Nút Reload -->
            <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2">
                <button
                    type="button"
                    @click="openTableForm()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 transition-colors whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i>
                    Thêm bàn
                </button>

                <div class="relative w-full sm:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input 
                        type="text" 
                        x-model="searchQuery"
                        placeholder="Tìm tên bàn..." 
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all"
                    >
                </div>

                <button 
                    @click="refreshData()"
                    title="Cập nhật dữ liệu từ API"
                    class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:text-orange-600 hover:bg-orange-50 active:scale-95 transition-all">
                    <i class="fa-solid fa-rotate-right" :class="{'fa-spin': isLoading}"></i>
                </button>
            </div>
        </div>

        @php
            $statusStyles = [
                'empty' => [
                    'card' => 'bg-white border-emerald-300 hover:border-emerald-500 hover:shadow-emerald-100',
                    'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    'icon' => 'fa-chair text-emerald-500',
                    'label' => 'Bàn Trống'
                ],
                'occupied' => [
                    'card' => 'bg-rose-50/40 border-rose-300 hover:border-rose-500 hover:shadow-rose-100',
                    'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                    'icon' => 'fa-users text-rose-500',
                    'label' => 'Đang Dùng'
                ],
                'cleaning' => [
                    'card' => 'bg-amber-50/40 border-amber-300 hover:border-amber-500 hover:shadow-amber-100',
                    'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                    'icon' => 'fa-broom text-amber-500',
                    'label' => 'Cần Dọn'
                ],
            ];
        @endphp

        <!-- Danh sách bàn phân khu vực -->
        @foreach ($tables as $zone => $items)
            <div 
                class="mb-10" 
                x-show="(activeZone === 'ALL' || activeZone === @js($zone)) && matchesSearch(@js($zone))"
                x-transition>
                
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-2.5 h-6 bg-orange-500 rounded-full"></span>
                    <h2 class="text-lg font-bold text-slate-800">{{ $zone }}</h2>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-600">
                        {{ $items->count() }} bàn
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach ($items as $t)
                        @php $style = $statusStyles[$t->status]; @endphp
                        <div 
                            x-show="shouldShowTable(@js($t->name), @js($zone))"
                            @click="openTableModal({{ $t->id }}, @js($t->name), @js($t->status))"
                            class="group relative p-4 rounded-2xl border-2 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-lg cursor-pointer flex flex-col justify-between h-36 {{ $style['card'] }}">
                            
                            <!-- Top: Tên bàn & Badge -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-slate-800 text-base group-hover:text-orange-600 transition-colors">
                                        {{ $t->name }}
                                    </h3>
                                    <span class="text-xs text-slate-400 font-medium">
                                        <i class="fa-solid fa-user-group text-[10px] mr-1"></i>{{ $t->capacity }} chỗ
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click.stop="openTableForm(@js($t->only(['id', 'name', 'zone', 'capacity', 'status'])))"
                                        aria-label="Sửa {{ $t->name }}"
                                        title="Sửa bàn"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <i class="fa-solid {{ $style['icon'] }} text-lg"></i>
                                </div>
                            </div>

                            <!-- Bottom: Trạng thái & Action -->
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $style['badge'] }}">
                                    {{ $style['label'] }}
                                </span>
                                <span class="text-xs text-slate-400 group-hover:text-orange-500 transition-colors">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </span>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>

    <div x-show="showTableForm" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100" @click.outside="showTableForm = false">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-900" x-text="tableForm.id ? 'Sửa thông tin bàn' : 'Thêm bàn mới'"></h3>
                <button type="button" @click="showTableForm = false" aria-label="Đóng" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form @submit.prevent="saveTable()" class="space-y-4">
                <div>
                    <label for="table-name" class="block text-sm font-medium text-slate-700 mb-1">Tên bàn</label>
                    <input id="table-name" type="text" x-model="tableForm.name" maxlength="255" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div>
                    <label for="table-zone" class="block text-sm font-medium text-slate-700 mb-1">Khu vực / Tầng</label>
                    <input id="table-zone" type="text" x-model="tableForm.zone" maxlength="255" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div>
                    <label for="table-capacity" class="block text-sm font-medium text-slate-700 mb-1">Sức chứa</label>
                    <input id="table-capacity" type="number" x-model.number="tableForm.capacity" min="1" max="255" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div>
                    <label for="table-status" class="block text-sm font-medium text-slate-700 mb-1">Trạng thái</label>
                    <select id="table-status" x-model="tableForm.status" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="empty">Trống</option>
                        <option value="occupied">Đang dùng</option>
                        <option value="cleaning">Cần dọn</option>
                    </select>
                </div>

                <p x-show="tableError" x-text="tableError" role="alert" class="text-sm text-rose-600"></p>
                <p x-show="tableForm.id && tableForm.status === 'occupied'" class="text-sm text-amber-700">Không thể xóa bàn đang có khách.</p>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <button
                        x-show="tableForm.id"
                        type="button"
                        @click="deleteTable()"
                        :disabled="isSaving || tableForm.status === 'occupied'"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-rose-200 text-rose-700 text-sm font-medium hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-trash"></i>
                        Xóa bàn
                    </button>
                    <div class="flex justify-end gap-2 ml-auto">
                        <button type="button" @click="showTableForm = false" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 font-medium text-sm">Hủy</button>
                        <button type="submit" :disabled="isSaving" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-medium text-sm disabled:opacity-60">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span x-text="isSaving ? 'Đang lưu...' : 'Lưu bàn'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Mở Phiên Dùng Bữa (Sprint 2) -->
    <div x-show="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100" @click.outside="showModal = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900" x-text="'Mở bàn: ' + selectedTableName"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form @submit.prevent="submitOpenSession()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Số lượng khách</label>
                    <input type="number" x-model.number="guestCount" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mã nhân viên phục vụ (Tùy chọn)</label>
                    <input type="number" x-model.number="employeeId" placeholder="VD: 1, 2..." class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 font-medium text-sm">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-medium text-sm shadow-sm">Xác nhận mở bàn</button>
                </div>
            </form>
        </div>
    </div>

    <!-- AlpineJS Manager Component -->
    <script>
        function posTableManager() {
            return {
                activeZone: 'ALL',
                searchQuery: '',
                isLoading: false,
                
                // Modal state
                showModal: false,
                selectedTableId: null,
                selectedTableName: '',
                guestCount: 2,
                employeeId: '',
                showTableForm: false,
                isSaving: false,
                tableError: '',
                tableForm: {
                    id: null,
                    name: '',
                    zone: 'Tầng 1',
                    capacity: 4,
                    status: 'empty',
                },

                shouldShowTable(tableName, zone) {
                    const matchZone = this.activeZone === 'ALL' || this.activeZone === zone;
                    const matchSearch = tableName.toLowerCase().includes(this.searchQuery.toLowerCase());
                    return matchZone && matchSearch;
                },

                matchesSearch(zone) {
                    return true;
                },

                openTableModal(tableId, tableName, status) {
                    if (status !== 'empty') {
                        alert('Bàn này đang có khách hoặc cần dọn!');
                        return;
                    }
                    this.selectedTableId = tableId;
                    this.selectedTableName = tableName;
                    this.guestCount = 2;
                    this.employeeId = '';
                    this.showModal = true;
                },

                openTableForm(table = null) {
                    this.tableForm = table
                        ? { ...table }
                        : { id: null, name: '', zone: 'Tầng 1', capacity: 4, status: 'empty' };
                    this.tableError = '';
                    this.showTableForm = true;
                },

                async saveTable() {
                    this.isSaving = true;
                    this.tableError = '';
                    const isEditing = this.tableForm.id !== null;

                    try {
                        const response = await fetch(isEditing ? `/api/tables/${this.tableForm.id}` : '/api/tables', {
                            method: isEditing ? 'PUT' : 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                name: this.tableForm.name,
                                zone: this.tableForm.zone,
                                capacity: this.tableForm.capacity,
                                status: this.tableForm.status,
                            }),
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.tableError = result.message || 'Không thể lưu thông tin bàn.';
                            return;
                        }

                        location.reload();
                    } catch (error) {
                        this.tableError = 'Không thể kết nối đến máy chủ.';
                    } finally {
                        this.isSaving = false;
                    }
                },

                async deleteTable() {
                    if (this.tableForm.status === 'occupied') {
                        this.tableError = 'Không thể xóa bàn đang có khách.';
                        return;
                    }

                    if (!window.confirm(`Bạn có chắc muốn xóa ${this.tableForm.name}?`)) {
                        return;
                    }

                    this.isSaving = true;
                    this.tableError = '';

                    try {
                        const response = await fetch(`/api/tables/${this.tableForm.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json' },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.tableError = result.message || 'Không thể xóa bàn.';
                            return;
                        }

                        location.reload();
                    } catch (error) {
                        this.tableError = 'Không thể kết nối đến máy chủ.';
                    } finally {
                        this.isSaving = false;
                    }
                },

                async submitOpenSession() {
                    try {
                        let res = await fetch('/api/dining-sessions', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                table_id: this.selectedTableId,
                                customer_count: this.guestCount,
                                employee_id: this.employeeId ? this.employeeId : null
                            })
                        });
                        let result = await res.json();
                        if (result.success) {
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    } catch (err) {
                        console.error('Lỗi mở bàn:', err);
                        alert('Có lỗi xảy ra khi kết nối đến server!');
                    }
                },

                async refreshData() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('/api/tables/status');
                        const data = await res.json();
                        if(data.success) {
                            console.log('API Synced successfully:', data.data);
                        }
                    } catch (error) {
                        console.error('API Error:', error);
                    } finally {
                        setTimeout(() => { this.isLoading = false; }, 500);
                    }
                }
            }
        }
    </script>

</body>
</html>