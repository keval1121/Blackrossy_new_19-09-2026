@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<form class="mb-4 flex flex-wrap gap-2" method="get">
    <input name="q" value="{{ request('q') }}" class="admin-input w-56" placeholder="Order, mobile, name">
    <select name="status" class="admin-input w-44">
        <option value="">All statuses</option>
        @foreach(\App\Enums\OrderStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <button class="rounded-xl bg-stone-900 px-4 text-white">Filter</button>
    <a href="{{ route('admin.orders.export', request()->query()) }}" class="rounded-xl border px-4 py-2 text-sm">Export</a>
</form>

{{-- Bulk status form shell; checkboxes use form="..." so nested stock/filter forms stay valid. --}}
<form id="orders-bulk-status" method="post" action="{{ route('admin.orders.bulk-status') }}" class="hidden">
    @csrf
</form>

<div id="orders-bulk-bar" class="mb-4 hidden items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm md:flex-wrap">
    <p class="font-medium text-stone-800"><span id="orders-selected-count">0</span> selected</p>
    <select form="orders-bulk-status" name="status" id="orders-bulk-status-select" class="admin-input w-48" required>
        <option value="">Set status…</option>
        @foreach(\App\Enums\OrderStatus::cases() as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
        @endforeach
    </select>
    <input form="orders-bulk-status" name="note" type="text" class="admin-input w-56" placeholder="Note (optional)" maxlength="255">
    <button type="submit" form="orders-bulk-status" id="orders-bulk-submit" class="rounded-xl bg-stone-900 px-4 py-2 text-white disabled:opacity-40" disabled>
        Update status
    </button>
</div>

<table class="w-full rounded-2xl bg-white text-left text-sm">
    <thead>
        <tr class="text-stone-400">
            <th class="w-10 p-3">
                <input type="checkbox" id="orders-select-all" aria-label="Select all orders on this page">
            </th>
            <th class="p-3">Order</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    @forelse($orders as $order)
        <tr class="border-t border-stone-100">
            <td class="p-3">
                <input
                    type="checkbox"
                    form="orders-bulk-status"
                    class="order-row-check"
                    name="ids[]"
                    value="{{ $order->id }}"
                    aria-label="Select {{ $order->order_number }}"
                >
            </td>
            <td class="p-3"><a class="text-amber-700" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
            <td>{{ $order->customer_name }}<div class="text-xs text-stone-400">{{ $order->mobile }}</div></td>
            <td>{{ money($order->total) }}</td>
            <td>{{ $order->status->label() }}</td>
            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
        </tr>
    @empty
        <tr class="border-t border-stone-100">
            <td colspan="6" class="p-6 text-center text-stone-400">No orders found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
<div class="mt-4">{{ $orders->links() }}</div>

@push('scripts')
<script>
(() => {
    const bar = document.getElementById('orders-bulk-bar');
    const countEl = document.getElementById('orders-selected-count');
    const selectAll = document.getElementById('orders-select-all');
    const statusSelect = document.getElementById('orders-bulk-status-select');
    const submitBtn = document.getElementById('orders-bulk-submit');
    const form = document.getElementById('orders-bulk-status');

    if (!bar || !countEl || !selectAll || !statusSelect || !submitBtn || !form) {
        return;
    }

    const checks = () => Array.from(document.querySelectorAll('.order-row-check'));

    const sync = () => {
        const selected = checks().filter((el) => el.checked);
        const total = checks().length;
        countEl.textContent = String(selected.length);
        bar.classList.toggle('hidden', selected.length === 0);
        bar.classList.toggle('flex', selected.length > 0);
        selectAll.checked = total > 0 && selected.length === total;
        selectAll.indeterminate = selected.length > 0 && selected.length < total;
        submitBtn.disabled = selected.length === 0 || !statusSelect.value;
    };

    selectAll.addEventListener('change', () => {
        checks().forEach((el) => {
            el.checked = selectAll.checked;
        });
        sync();
    });

    document.addEventListener('change', (event) => {
        if (event.target.classList?.contains('order-row-check') || event.target === statusSelect) {
            sync();
        }
    });

    form.addEventListener('submit', (event) => {
        const selected = checks().filter((el) => el.checked).length;
        if (selected === 0 || !statusSelect.value) {
            event.preventDefault();
            return;
        }
        const label = statusSelect.options[statusSelect.selectedIndex]?.text || statusSelect.value;
        if (!confirm(`Update ${selected} order(s) to “${label}”?`)) {
            event.preventDefault();
        }
    });

    sync();
})();
</script>
@endpush
@endsection
