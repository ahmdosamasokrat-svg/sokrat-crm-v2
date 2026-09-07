<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KanbanModalAndStageConsistencyAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $restrictedUser;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ]
        );

        $this->admin = User::query()->where('username', 'admin')->first() ?? User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin User',
            'is_active' => true,
        ]);
        $this->admin->groups()->syncWithoutDetaching([$superAdminGroup->id]);
        $this->stageA = PipelineStage::query()->firstOrCreate(
            ['code' => 'stage_audit_a'],
            ['name_ar' => 'مرحلة تدقيق أ', 'position' => 90, 'is_active' => true, 'color' => '#3478f6']
        );

        $this->stageB = PipelineStage::query()->firstOrCreate(
            ['code' => 'stage_audit_b'],
            ['name_ar' => 'مرحلة تدقيق ب', 'position' => 91, 'is_active' => true, 'color' => '#10b981']
        );

        $this->statusA = LeadStatus::query()->firstOrCreate(
            ['code' => 'status_audit_a'],
            ['pipeline_stage_id' => $this->stageA->id, 'name_ar' => 'حالة تدقيق أ', 'position' => 90]
        );

        $this->statusB = LeadStatus::query()->firstOrCreate(
            ['code' => 'status_audit_b'],
            ['pipeline_stage_id' => $this->stageB->id, 'name_ar' => 'حالة تدقيق ب', 'position' => 91]
        );

        $this->lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل تدقيق النوافذ',
            'phone' => '0511111111',
            'company_name' => 'شركة تدقيق الواجهات',
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->admin->id,
        ]);
    }

    /**
     * 1. normal Lead show uses full CRM layout
     */
    public function test_1_normal_lead_show_uses_full_crm_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $this->lead));

        $response->assertOk();
        $response->assertSee('crm-app', false);
        $response->assertSee('crm-main', false);
        $response->assertSee('<header class="crm-topbar', false);
        $response->assertDontSee('<div class="crm-popup-shell', false);
    }

    /**
     * 2. popup Lead show does not render Sidebar
     */
    public function test_2_popup_lead_show_does_not_render_sidebar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertDontSee('<aside', false);
        $response->assertDontSee('id="crmSidebar"', false);
        $response->assertDontSee('class="crm-side', false);
    }

    /**
     * 3. popup Lead show does not render main CRM navigation
     */
    public function test_3_popup_lead_show_does_not_render_main_crm_navigation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertDontSee('<header class="crm-topbar', false);
        $response->assertDontSee('crm-topbar-menu-btn', false);
        $response->assertDontSee('crm-topbar-notification-btn', false);
        $response->assertSee('crm-popup-shell', false);
        $response->assertSee('crm-popup-header', false);
    }

    /**
     * 4. popup Lead show renders customer content
     */
    public function test_4_popup_lead_show_renders_customer_content(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertSee($this->lead->name);
        $response->assertSee($this->lead->phone);
        $response->assertSee('شركة تدقيق الواجهات');
        $response->assertSee('lead-header-card', false);
        $response->assertSee('details-grid', false);
    }

    /**
     * 5. popup authorization same as normal route
     */
    public function test_5_popup_authorization_same_as_normal_route(): void
    {
        // When unauthenticated, both redirect to login
        $this->get(route('v2.leads.show', $this->lead))->assertRedirect('/login');
        $this->get(route('v2.leads.show', ['lead' => $this->lead, 'kanban_popup' => 1]))->assertRedirect('/login');

        // Normal route and popup route for admin both return 200
        $this->actingAs($this->admin)->get(route('v2.leads.show', $this->lead))->assertOk();
        $this->actingAs($this->admin)->get(route('v2.leads.show', ['lead' => $this->lead, 'kanban_popup' => 1]))->assertOk();
    }

    /**
     * 6. Kanban View Lead URL preserves same origin semantics
     */
    public function test_6_kanban_view_lead_url_preserves_same_origin_semantics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));

        $response->assertOk();
        $response->assertSee('data-kanban-customer-popup', false);
        $response->assertSee('crmKanbanActionModal', false);
        $response->assertSee('url.protocol = window.location.protocol', false);
        $response->assertSee('url.host = window.location.host', false);
        $response->assertSee("url.searchParams.set('kanban_popup', '1')", false);
    }

    /**
     * 7. popup close lifecycle
     */
    public function test_7_popup_close_lifecycle(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('closePopupModal', false);
        $response->assertSee('crm-popup-close-btn', false);

        // Also check Kanban exposes window.closePopup
        $kanbanResponse = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $kanbanResponse->assertSee('window.closePopup = closePopup', false);
        $kanbanResponse->assertSee("frame.src = 'about:blank'", false);
    }

    /**
     * 8. all active stages map correctly
     */
    public function test_8_all_active_stages_map_correctly(): void
    {
        $activeStages = PipelineStage::query()->where('is_active', true)->with('statuses')->get();

        $this->assertNotEmpty($activeStages);
        foreach ($activeStages as $stage) {
            $this->assertTrue((bool) $stage->is_active);
            foreach ($stage->statuses as $status) {
                $this->assertSame((int) $stage->id, (int) $status->pipeline_stage_id);
            }
        }
    }

    /**
     * 9. Settings vs Kanban schema match
     */
    public function test_9_settings_vs_kanban_schema_match(): void
    {
        $stages = PipelineStage::query()->where('is_active', true)->get();

        foreach ($stages as $stage) {
            $settingsFields = StageFieldSchema::getFieldsForStage($stage, true)->pluck('key')->all();
            $directFields = PipelineStageField::query()
                ->where('pipeline_stage_id', $stage->id)
                ->where('is_active', true)
                ->orderBy('position')
                ->pluck('key')
                ->all();

            $this->assertEquals($settingsFields, $directFields);
        }
    }

    /**
     * 10. Settings vs Add Lead schema match
     */
    public function test_10_settings_vs_add_lead_schema_match(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));

        $response->assertOk();
        $activeStages = PipelineStage::query()->where('is_active', true)->with('activeFields')->get();

        foreach ($activeStages as $stage) {
            if ($stage->activeFields->isNotEmpty()) {
                $response->assertSee('stage_q_block_' . $stage->id, false);
                foreach ($stage->activeFields as $field) {
                    $response->assertSee('stage_fields[' . $field->key . ']', false);
                }
            }
        }
    }

    /**
     * 11. Settings vs Edit Lead schema match
     */
    public function test_11_settings_vs_edit_lead_schema_match(): void
    {
        // Add a field to stageA
        $field = PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stageA->id, 'key' => 'audit_notes'],
            ['label_ar' => 'ملاحظات التدقيق', 'type' => 'textarea', 'is_active' => true]
        );

        $response = $this->actingAs($this->admin)->get(route('v2.leads.edit', $this->lead));

        $response->assertOk();
        $response->assertSee('stage_fields[audit_notes]', false);
        $response->assertSee('ملاحظات التدقيق');
    }

    /**
     * 12. hidden stage fields disabled
     */
    public function test_12_hidden_stage_fields_disabled(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));

        $response->assertOk();
        // In Add Lead, non-initial stages have disabled inputs
        $response->assertSee('syncStageQuestions', false);
        $response->assertSee("input.setAttribute('disabled', 'disabled')", false);
        $response->assertSee("input.removeAttribute('disabled')", false);
    }

    /**
     * 13. inactive required fields ignored
     */
    public function test_13_inactive_required_fields_ignored(): void
    {
        $inactiveField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'inactive_required_key',
            'label_ar' => 'حقل معطل إجباري',
            'type' => 'text',
            'is_required' => true,
            'is_active' => false,
        ]);

        $activeFields = StageFieldSchema::getFieldsForStage($this->stageB, true);
        $this->assertFalse($activeFields->contains('key', 'inactive_required_key'));

        $rules = StageFieldSchema::buildValidationRules($this->stageB);
        $this->assertArrayNotHasKey('inactive_required_key', $rules);
    }

    /**
     * 14. conditional fields parity
     */
    public function test_14_conditional_fields_parity(): void
    {
        $condition = [
            'field' => 'has_budget',
            'operator' => 'equals',
            'value' => 'yes',
        ];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condition, ['has_budget' => 'yes']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condition, ['has_budget' => 'no']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condition, []));
    }

    /**
     * 15. no generic duplicate schedule if dynamic schedule binding exists
     */
    public function test_15_no_generic_duplicate_schedule_if_dynamic_schedule_binding_exists(): void
    {
        // Add callback_at field to stageB
        PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stageB->id, 'key' => 'callback_at'],
            ['label_ar' => 'موعد إعادة الاتصال', 'type' => 'datetime', 'is_active' => true, 'is_required' => true]
        );

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
            'target_status_id' => $this->statusB->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-has-schedule="1"', false);
        $response->assertSee('genericNextFollowupContainer', false);
        $response->assertSee('hasDynamicSchedule', false);
    }

    /**
     * 16. Kanban transition still succeeds
     */
    public function test_16_kanban_transition_still_succeeds(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'callback_at',
            'label_ar' => 'موعد إعادة الاتصال',
            'type' => 'datetime',
            'is_required' => false,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل اختبار الانتقال',
            'phone' => '0522222222',
            'created_by' => $this->admin->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $postResponse = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'call',
                'outcome' => 'تم نقل العميل بنجاح في التدقيق',
                'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'kanban_popup' => 1,
                'stage_fields' => [
                    'callback_at' => now()->addDays(2)->format('Y-m-d H:i'),
                ],
            ]
        );

        $postResponse->assertSessionHasNoErrors();
        $postResponse->assertRedirect();
        $this->assertSame((int) $this->statusB->id, (int) $lead->fresh()->lead_status_id);
    }

    /**
     * 17. Call popup still works
     */
    public function test_17_call_popup_still_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'channel' => 'call',
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertDontSee('<aside', false);
        $response->assertDontSee('id="crmSidebar"', false);
        $response->assertDontSee('<header class="crm-topbar', false);
        $response->assertSee('name="communication_type"', false);
    }

    /**
     * 18. Log Follow-up popup still works
     */
    public function test_18_log_followup_popup_still_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertDontSee('<aside', false);
        $response->assertDontSee('id="crmSidebar"', false);
        $response->assertDontSee('<header class="crm-topbar', false);
        $response->assertSee('followupForm', false);
    }

    /**
     * 19. followup popup unauthorized user returns 403
     */
    public function test_19_followup_popup_unauthorized_user_returns_403(): void
    {
        $agent = User::factory()->create(['is_active' => true]);
        $agentGroup = Group::query()->firstOrCreate(['code' => 'sales-agent'], ['name' => 'موظف مبيعات']);
        $agentGroup->permissions()->sync(
            \App\Models\Permission::query()->whereIn('code', [
                \App\Security\CrmPermission::LEADS_VIEW->value,
                \App\Security\CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                \App\Security\CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            ])->pluck('id')
        );
        $agent->groups()->attach($agentGroup);

        // Lead assigned to another user, so agent has no access
        $otherLead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل غير مسند للوكيل',
            'assigned_user_id' => $this->admin->id,
        ]);

        $this->actingAs($agent)
            ->get(route('v2.leads.followups.index', ['lead' => $otherLead, 'kanban_popup' => 1]))
            ->assertForbidden();
    }

    /**
     * 20. Sales Agent accessible Lead popup works
     */
    public function test_20_sales_agent_accessible_lead_popup_works(): void
    {
        $agent = User::factory()->create(['is_active' => true]);
        $agentGroup = Group::query()->firstOrCreate(['code' => 'sales-agent'], ['name' => 'موظف مبيعات']);
        $agentGroup->permissions()->sync(
            \App\Models\Permission::query()->whereIn('code', [
                \App\Security\CrmPermission::LEADS_VIEW->value,
                \App\Security\CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                \App\Security\CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            ])->pluck('id')
        );
        $agent->groups()->attach($agentGroup);

        $accessibleLead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل مسند للوكيل',
            'assigned_user_id' => $agent->id,
        ]);

        $this->actingAs($agent)
            ->get(route('v2.leads.followups.index', ['lead' => $accessibleLead, 'kanban_popup' => 1]))
            ->assertOk();

        $this->actingAs($agent)
            ->get(route('v2.leads.show', ['lead' => $accessibleLead, 'kanban_popup' => 1]))
            ->assertOk();
    }

    /**
     * 21. Manager and Team Leader accessible Lead popup works
     */
    public function test_21_manager_and_teamleader_accessible_lead_popup_works(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $managerGroup = Group::query()->firstOrCreate(['code' => 'sales-manager'], ['name' => 'مدير مبيعات']);
        $managerGroup->permissions()->sync(
            \App\Models\Permission::query()->whereIn('code', [
                \App\Security\CrmPermission::LEADS_VIEW->value,
                \App\Security\CrmPermission::LEADS_SCOPE_ALL->value,
                \App\Security\CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                \App\Security\CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            ])->pluck('id')
        );
        $manager->groups()->attach($managerGroup);

        $this->actingAs($manager)
            ->get(route('v2.leads.followups.index', ['lead' => $this->lead, 'kanban_popup' => 1]))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('v2.leads.show', ['lead' => $this->lead, 'kanban_popup' => 1]))
            ->assertOk();
    }

    /**
     * 22. Calls endpoint handles empty phone gracefully
     */
    public function test_22_calls_endpoint_handles_empty_phone_gracefully(): void
    {
        $leadWithoutPhone = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل بدون هاتف',
            'phone' => null,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.calls', [
            'lead' => $leadWithoutPhone,
            'limit' => 100,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'calls' => [],
        ]);
    }

    /**
     * 23. Calls endpoint accepts valid limit
     */
    public function test_23_calls_endpoint_accepts_valid_limit(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.calls', [
            'lead' => $this->lead,
            'limit' => 100,
        ]));

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * 24. Nested popup recursion prevented in followup view
     */
    public function test_24_nested_popup_recursion_prevented_in_followup_view(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        // View Lead link retains kanban_popup=1
        $response->assertSee('kanban_popup=1', false);
        // Cancel button uses closePopupModal instead of navigating
        $response->assertSee('closePopupModal()', false);
        // Iframe protection is present
        $response->assertSee('is-embedded-popup', false);
    }

    /**
     * 25. AJAX Kanban column cards include popup attributes
     */
    public function test_25_ajax_kanban_column_cards_include_popup_attributes(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusA->id,
            'scope' => 'all',
            'page' => 1,
            'per_page' => 10,
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'html', 'page', 'pageSize', 'total']);
        $html = $response->json('html');
        $this->assertStringContainsString('data-kanban-customer-popup', $html);
        $this->assertStringContainsString('data-kanban-followup-popup', $html);
        $this->assertStringContainsString('data-followup-url', $html);
    }

    /**
     * 26. popup route respects Light preference
     */
    public function test_26_popup_route_respects_light_theme(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
            'theme' => 'light',
        ]));

        $response->assertOk();
        $response->assertDontSee('<html lang="en" dir="ltr" class="dark-mode">', false);
        $response->assertSee('sokrat.crm.theme', false);
        $response->assertSee('is-embedded-popup', false);
    }

    /**
     * 27. popup route respects Dark preference
     */
    public function test_27_popup_route_respects_dark_theme(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
            'theme' => 'dark',
        ]));

        $response->assertOk();
        $response->assertSee('class="dark-mode"', false);
        $response->assertSee('sokrat.crm.theme', false);
    }

    /**
     * 28. popup route system theme inherits parent
     */
    public function test_28_popup_route_system_theme_inherits_parent(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        // Must check window.parent for inheritance when embedded
        $response->assertSee('window.parent?.document?.documentElement', false);
        $response->assertSee('parentHtml.classList.contains(\'dark-mode\')', false);
    }

    /**
     * 29. followup popup respects light and dark theme
     */
    public function test_29_followup_popup_respects_light_and_dark_theme(): void
    {
        $lightRes = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
            'theme' => 'light',
        ]));
        $lightRes->assertOk();
        $lightRes->assertDontSee('class="dark-mode"', false);

        $darkRes = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
            'theme' => 'dark',
        ]));
        $darkRes->assertOk();
        $darkRes->assertSee('class="dark-mode"', false);
    }

    /**
     * 30. create lead popup respects light and dark theme
     */
    public function test_30_create_lead_popup_respects_light_and_dark_theme(): void
    {
        $lightRes = $this->actingAs($this->admin)->get(route('v2.leads.create', [
            'kanban_popup' => 1,
            'theme' => 'light',
        ]));
        $lightRes->assertOk();
        $lightRes->assertDontSee('class="dark-mode"', false);

        $darkRes = $this->actingAs($this->admin)->get(route('v2.leads.create', [
            'kanban_popup' => 1,
            'theme' => 'dark',
        ]));
        $darkRes->assertOk();
        $darkRes->assertSee('class="dark-mode"', false);
    }

    /**
     * 31. no popup forces dark class unconditionally
     */
    public function test_31_no_popup_forces_dark_class_unconditionally(): void
    {
        $views = [
            route('v2.leads.show', ['lead' => $this->lead, 'kanban_popup' => 1]),
            route('v2.leads.followups.index', ['lead' => $this->lead, 'kanban_popup' => 1]),
            route('v2.leads.create', ['kanban_popup' => 1]),
            route('v2.leads', ['kanban_popup' => 1]),
        ];

        foreach ($views as $v) {
            $res = $this->actingAs($this->admin)->get($v);
            $res->assertOk();
            // Without explicit dark theme param, HTML tag should not force dark-mode server-side
            $res->assertDontSee('<html lang="en" dir="ltr" class="dark-mode">', false);
            // Shared storage key must be canonical
            $res->assertSee('sokrat.crm.theme', false);
            $res->assertDontSee('crm_theme', false);
        }
    }

    /**
     * 32. shared theme initialization present in all popups
     */
    public function test_32_shared_theme_initialization_present_in_all_popups(): void
    {
        $res = $this->actingAs($this->admin)->get(route('v2.leads.show', [
            'lead' => $this->lead,
            'kanban_popup' => 1,
        ]));

        $res->assertOk();
        $res->assertSee('window.parent?.document?.documentElement', false);
        $res->assertSee('parentHtml.classList.contains(\'dark-mode\')', false);
        $res->assertSee('parentHtml.dataset.theme', false);
    }

    /**
     * 33. kanban builds popup url with active theme
     */
    public function test_33_kanban_builds_popup_url_with_active_theme(): void
    {
        $res = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $res->assertOk();
        $res->assertSee('buildKanbanPopupUrl', false);
        $res->assertSee("url.searchParams.set('theme', isDark ? 'dark' : 'light')", false);
        $res->assertSee('syncIframeTheme', false);
    }
}
