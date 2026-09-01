<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FollowupCustomerField;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\FollowupCustomerFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowupCustomerFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                ['module' => $permission->module(), 'name_ar' => $permission->label()],
            );
        }

        $group = Group::query()->updateOrCreate(
            ['code' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ],
        );
        $group->permissions()->sync(Permission::query()->pluck('id'));

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->groups()->attach($group);
    }

    protected function tearDown(): void
    {
        FollowupCustomerFieldSchema::flushCache();
        parent::tearDown();
    }

    public function test_system_fields_are_seeded_and_settings_can_add_a_custom_field(): void
    {
        $this->assertDatabaseHas('followup_customer_fields', [
            'key' => 'company_name',
            'lead_attribute' => 'company_name',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->withSession(['locale' => 'ar'])
            ->get(route('v2.settings.followup-customer-fields.index'))
            ->assertOk()
            ->assertSee('اسم الشركة');

        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->post(
            route('v2.settings.followup-customer-fields.store'),
            [
                'label_ar' => 'المنتج المفضل',
                'label_en' => 'Favorite Product',
                'type' => 'select',
                'options_raw' => "سنترال | Call Center | call-center\nERP | ERP | erp",
                'is_required' => 1,
            ],
        );

        $response->assertRedirect(route('v2.settings.followup-customer-fields.index'));
        $this->assertDatabaseHas('followup_customer_fields', [
            'key' => 'favorite_product',
            'label_ar' => 'المنتج المفضل',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
        ]);
    }

    public function test_full_followup_updates_canonical_and_custom_fields_with_history(): void
    {
        $customField = FollowupCustomerField::query()->create([
            'key' => 'preferred_contact_time',
            'label_ar' => 'وقت التواصل المناسب',
            'label_en' => 'Preferred Contact Time',
            'type' => 'text',
            'is_active' => true,
            'position' => 20,
        ]);
        [$lead, $status] = $this->leadAndStatus(['company_name' => 'Old Company']);

        $this->actingAs($this->admin)->withSession(['locale' => 'ar'])
            ->get(route('v2.leads.followups.index', $lead))
            ->assertOk()
            ->assertSee('وقت التواصل المناسب');

        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $status->id,
                'communication_type' => 'other',
                'outcome' => 'تم تحديث بيانات العميل',
                'customer_field_presence' => ['company_name', $customField->key],
                'customer_fields' => [
                    'company_name' => 'New Company',
                    $customField->key => 'بعد الخامسة مساءً',
                ],
            ],
        );

        $response->assertRedirect();
        $lead->refresh();
        $this->assertSame('New Company', $lead->company_name);
        $this->assertSame('بعد الخامسة مساءً', $lead->custom_fields['preferred_contact_time']);

        $changes = LeadFollowup::query()->where('lead_id', $lead->id)->latest('id')->firstOrFail()->field_changes;
        $this->assertTrue(collect($changes)->contains(
            fn (array $change): bool => $change['field'] === 'company_name'
                && $change['label'] === 'اسم الشركة'
                && $change['old'] === 'Old Company'
                && $change['new'] === 'New Company',
        ));
        $this->assertTrue(collect($changes)->contains(
            fn (array $change): bool => $change['field'] === 'preferred_contact_time'
                && $change['label'] === 'وقت التواصل المناسب',
        ));
    }

    public function test_hidden_or_omitted_fields_are_preserved(): void
    {
        FollowupCustomerField::query()->where('key', 'company_name')->firstOrFail()->update([
            'is_active' => false,
        ]);
        [$lead, $status] = $this->leadAndStatus([
            'company_name' => 'Company Must Stay',
            'users_count' => 75,
            'job_title' => 'IT Manager',
        ]);

        $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $status->id,
                'communication_type' => 'other',
                'outcome' => 'متابعة بدون تعديل بيانات الشركة',
            ],
        )->assertRedirect();

        $lead->refresh();
        $this->assertSame('Company Must Stay', $lead->company_name);
        $this->assertSame(75, $lead->users_count);
        $this->assertSame('IT Manager', $lead->job_title);
        $this->actingAs($this->admin)
            ->get(route('v2.leads.followups.index', $lead))
            ->assertOk()
            ->assertDontSee('Company Must Stay');
    }

    public function test_canonical_column_constraints_are_enforced(): void
    {
        FollowupCustomerField::query()->where('key', 'users_count')->firstOrFail()->update([
            'is_active' => true,
        ]);
        [$lead, $status] = $this->leadAndStatus(['company_name' => 'Original Company']);

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $status->id,
                'communication_type' => 'other',
                'outcome' => 'اختبار حدود الحقول',
                'customer_field_presence' => ['company_name', 'users_count'],
                'customer_fields' => [
                    'company_name' => str_repeat('x', 151),
                    'users_count' => -1,
                ],
            ],
        );

        $response->assertSessionHasErrors(['company_name', 'users_count']);
        $this->assertSame('Original Company', $lead->fresh()->company_name);
        $this->assertDatabaseCount('lead_followups', 0);
    }

    public function test_used_list_option_values_cannot_be_removed(): void
    {
        $field = FollowupCustomerField::query()->create([
            'key' => 'customer_segment',
            'label_ar' => 'شريحة العميل',
            'label_en' => 'Customer Segment',
            'type' => 'select',
            'options' => [
                ['value' => 'small', 'label_ar' => 'صغير', 'label_en' => 'Small'],
                ['value' => 'large', 'label_ar' => 'كبير', 'label_en' => 'Large'],
            ],
            'is_active' => true,
            'position' => 20,
        ]);
        [$lead] = $this->leadAndStatus([
            'custom_fields' => ['customer_segment' => 'small'],
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('v2.settings.followup-customer-fields.update', $field),
            [
                'label_ar' => 'شريحة العميل',
                'label_en' => 'Customer Segment',
                'type' => 'select',
                'options_raw' => 'كبير | Large | large',
            ],
        );

        $response->assertSessionHasErrors('options_raw');
        $this->assertSame(['small', 'large'], array_column($field->fresh()->options, 'value'));
        $this->assertSame('small', $lead->fresh()->custom_fields['customer_segment']);
    }

    public function test_unknown_or_inactive_field_submission_is_rejected(): void
    {
        [$lead, $status] = $this->leadAndStatus();

        $response = $this->actingAs($this->admin)->from(route('v2.leads.followups.index', $lead))->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $status->id,
                'communication_type' => 'other',
                'outcome' => 'محاولة تعديل حقل غير متاح',
                'customer_field_presence' => ['unknown_field'],
                'customer_fields' => ['unknown_field' => 'tampered'],
            ],
        );

        $response->assertRedirect(route('v2.leads.followups.index', $lead));
        $response->assertSessionHasErrors('customer_fields');
        $this->assertDatabaseCount('lead_followups', 0);
    }

    private function leadAndStatus(array $attributes = []): array
    {
        $stage = PipelineStage::query()->create([
            'code' => 'followup_customer_fields_'.uniqid(),
            'name_ar' => 'مرحلة اختبار المتابعة',
            'position' => 50,
            'color' => '#64748b',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $status = $stage->statuses()->firstOrFail();
        $status->update(['code' => 'new', 'name_ar' => 'جديد']);
        $lead = Lead::query()->create([
            ...$attributes,
            'lead_status_id' => $status->id,
            'name' => 'عميل اختبار الحقول',
            'phone' => '01000000000',
            'source' => 'test',
        ]);

        return [$lead, $status];
    }
}
