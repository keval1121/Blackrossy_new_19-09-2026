<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderBulkStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_status_for_multiple_orders(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'orders-bulk@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $first = $this->order('BR1001', OrderStatus::Pending);
        $second = $this->order('BR1002', OrderStatus::Pending);
        $untouched = $this->order('BR1003', OrderStatus::Pending);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.orders.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'status' => OrderStatus::Confirmed->value,
            'note' => 'Bulk confirm',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', '2 order statuses updated.');

        $this->assertSame(OrderStatus::Confirmed, $first->fresh()->status);
        $this->assertSame(OrderStatus::Confirmed, $second->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $untouched->fresh()->status);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $first->id,
            'to_status' => OrderStatus::Confirmed->value,
            'note' => 'Bulk confirm',
            'admin_id' => $admin->id,
        ]);
    }

    public function test_bulk_status_requires_selected_orders_and_valid_status(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'orders-bulk-validate@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->from(route('admin.orders.index'))->post(route('admin.orders.bulk-status'), [
            'ids' => [],
            'status' => 'not-a-status',
        ]);

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHasErrors(['ids', 'status']);
    }

    public function test_orders_index_shows_bulk_status_controls(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Admin',
            'email' => 'orders-bulk-ui@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->order('BR1004', OrderStatus::Pending);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('name="ids[]"', false);
        $response->assertSee(route('admin.orders.bulk-status'), false);
        $response->assertSee('Update status');
    }

    private function order(string $number, OrderStatus $status): Order
    {
        return Order::query()->create([
            'order_number' => $number,
            'status' => $status,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => 'pending',
            'subtotal' => 999,
            'discount' => 0,
            'delivery_charge' => 0,
            'total' => 999,
            'customer_name' => 'Test Buyer',
            'mobile' => '9876543210',
        ]);
    }
}
