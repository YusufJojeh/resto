<?php

namespace Tests\Feature\RestoCafe;

use App\Notifications\OperationalNotification;

class NotificationsWorkflowTest extends RestoCafeTestCase
{
    public function test_notifications_list_and_mark_read(): void
    {
        $admin = $this->admin();
        $admin->notify(new OperationalNotification('new_order', 'New order', 'Order created', $admin->branch_id));

        $notification = $admin->notifications()->latest()->firstOrFail();

        $this->actingAs($admin)->get(route('notifications.index'))->assertOk();
        $this->actingAs($admin)->post(route('notifications.read', $notification->id))->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read(): void
    {
        $admin = $this->admin();
        $admin->notify(new OperationalNotification('new_order', 'New order', 'Order created', $admin->branch_id));
        $admin->notify(new OperationalNotification('new_order', 'New order', 'Order created', $admin->branch_id));

        $this->actingAs($admin)->post(route('notifications.read_all'))->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_routes_respect_feature_flag(): void
    {
        config()->set('features.notifications.enabled', false);
        $admin = $this->admin();
        $admin->notify(new OperationalNotification('new_order', 'New order', 'Order created', $admin->branch_id));
        $notification = $admin->notifications()->latest()->firstOrFail();

        $this->actingAs($admin)->get(route('notifications.index'))->assertNotFound();
        $this->actingAs($admin)->post(route('notifications.read', $notification->id))->assertNotFound();
        $this->actingAs($admin)->post(route('notifications.read_all'))->assertNotFound();
        $this->actingAs($admin)->delete(route('notifications.destroy', $notification->id))->assertNotFound();
    }
}
