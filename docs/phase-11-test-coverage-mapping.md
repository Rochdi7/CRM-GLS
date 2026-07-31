# Phase 11H — Test Coverage Mapping

Maps every Livewire-era test file identified in `docs/phase-11-livewire-
cleanup-audit.md` Section G to its Inertia-side replacement, and documents
exactly which test method covers which behavior. No test is deleted in this
document — this is the coverage-parity proof required before any Livewire
test file is removed in Phase 11D.

**Status: all identified coverage gaps are now closed.** Every Livewire-era
test file with real business-behavior coverage now has a confirmed,
method-level-verified Inertia equivalent. 15 new test methods were added
across 5 files; all pass individually, pass together (108/108), and were
cross-checked against the still-on-disk Livewire tests to confirm true
behavioral parity (51/51 Livewire tests still pass unchanged).

---

## Gap closure — all 6 confirmed gaps, now closed

The audit (`docs/phase-11-livewire-cleanup-audit.md` §G.5) initially flagged
2 confirmed gaps in `CenterScopingTest.php`. Closing those triggered a
closer look at the 4 "needs confirmation" sub-feature files and the 2
flagged `SuperAdminProtectionTest` methods, which turned up 13 further real
gaps. All of the following are now closed:

| # | Livewire behavior | Source file : method | New Inertia test | Target file |
|---|---|---|---|---|
| 1 | Salles tab list follows the active context center | `CenterScopingTest::test_salles_tab_is_scoped_to_the_selected_center` | `test_salles_tab_is_scoped_to_the_selected_center` | `Settings/SettingsTest.php` |
| 2 | Users list follows employee center, admin accounts stay visible everywhere | `CenterScopingTest::test_users_list_follows_the_employee_center_but_keeps_admin_accounts` | `test_index_follows_the_employee_center_but_keeps_admin_accounts` | `Inertia/UsersInertiaTest.php` |
| 3 | Stale domaine/examen_type dropped server-side when niveau changes away from a German track | `StudentOrientationTest::test_a_stale_orientation_is_not_persisted_after_a_level_change` | `test_changing_the_level_away_from_an_orientation_track_drops_the_stale_value` | `Students/StudentsInertiaCrudTest.php` |
| 4 | CIN stored and searchable | `StudentOrientationTest::test_the_student_cin_is_stored_and_searchable` | `test_the_student_cin_is_stored_and_searchable` | `Students/StudentsInertiaCrudTest.php` |
| 5 | New-student-in-enrollment: CIN + professional field (Ausbildung) saved | `InscriptionStudentFieldsTest::test_a_new_student_is_created_with_cin_and_a_professional_field` | `test_new_student_mode_saves_cin_and_a_professional_field` | `Inscriptions/InscriptionsInertiaCrudTest.php` |
| 6 | New-student-in-enrollment: entrance exam (Studium) saved | `InscriptionStudentFieldsTest::test_a_new_student_can_be_created_with_an_entrance_exam` | `test_new_student_mode_saves_an_entrance_exam` | `Inscriptions/InscriptionsInertiaCrudTest.php` |
| 7 | New-student-in-enrollment: domaine required for Arbeit | `InscriptionStudentFieldsTest::test_the_field_is_required_when_the_track_asks_for_it` | `test_new_student_mode_requires_domaine_for_arbeit` | `Inscriptions/InscriptionsInertiaCrudTest.php` |
| 8 | New-student-in-enrollment: unknown parent relation rejected | `InscriptionStudentFieldsTest::test_an_unknown_parent_relation_is_rejected` | `test_new_student_mode_rejects_an_unknown_parent_relation` | `Inscriptions/InscriptionsInertiaCrudTest.php` |
| 9 | Employees searchable by address | `EmployeeProfileFieldsTest::test_employees_can_be_searched_by_address` | `test_employees_can_be_searched_by_address` | `People/EmployeesInertiaCrudTest.php` |
| 10 | Single-file photo collection: a new upload replaces the previous one | `EmployeeProfileFieldsTest::test_uploading_a_new_photo_replaces_the_previous_one` | `test_uploading_a_new_photo_replaces_the_previous_one` | `People/EmployeesInertiaCrudTest.php` |
| 11 | Oversized photo (> 2MB) rejected | `EmployeeProfileFieldsTest::test_an_oversized_photo_is_rejected` | `test_an_oversized_photo_is_rejected` | `People/EmployeesInertiaCrudTest.php` |
| 12 | Journal `all` scope shows every accessible till | `CaisseManagementPageTest::test_all_scope_shows_every_accessible_till` | `test_journal_all_scope_shows_every_accessible_till` | `Finance/CaissesInertiaCrudTest.php` |
| 13 | Journal `mine` scope self-heals a missing till (auto-provisioning backfill) | `CaisseManagementPageTest::test_mine_scope_self_heals_a_missing_till` | `test_journal_mine_scope_self_heals_a_missing_till` | `Finance/CaissesInertiaCrudTest.php` |
| 14 | A transfers-only user can open the page; the legacy `/caisse-transfers` URL deep-links to the transferts tab | `CaisseManagementPageTest::test_a_transfers_only_user_can_open_the_page` + `test_the_legacy_transfers_url_deep_links_to_its_tab` | `test_a_transfers_only_user_can_open_the_page_and_the_legacy_url_redirects` | `Finance/CaissesInertiaCrudTest.php` |
| 15 | The last super-admin cannot lose the role via authorization update | `SuperAdminProtectionTest::test_the_last_super_admin_cannot_lose_the_role` | `test_the_last_super_admin_cannot_lose_the_role` | `Inertia/UsersInertiaTest.php` |
| 16 | A super-admin can be demoted when another one remains | `SuperAdminProtectionTest::test_a_super_admin_can_be_demoted_when_another_remains` | `test_a_super_admin_can_be_demoted_when_another_remains` | `Inertia/UsersInertiaTest.php` |

All 16 new tests were verified in two passes:

1. **Standalone/grouped run** of all 6 modified Inertia test files together:
   `tests/Feature/Backoffice/{Students/StudentsInertiaCrudTest,
   Inscriptions/InscriptionsInertiaCrudTest,
   People/EmployeesInertiaCrudTest, Finance/CaissesInertiaCrudTest,
   Inertia/UsersInertiaTest, Settings/SettingsTest}.php` →
   **108/108 passing**.
2. **Parity run** of the corresponding still-on-disk Livewire test files
   (`Students/StudentOrientationTest`,
   `Inscriptions/InscriptionStudentFieldsTest`,
   `People/EmployeeProfileFieldsTest`, `Finance/CaisseManagementPageTest`,
   `Authorization/SuperAdminProtectionTest`, `Context/CenterScopingTest`) →
   **51/51 still passing**, confirming the new Inertia tests describe the
   same real behavior rather than a superficial rewrite that happens to
   pass.

Every new test asserts through the real HTTP/Inertia layer (`$this->post`/
`$this->put`/`$this->get` + `assertInertia`/`assertSessionHasErrors`/
`assertJsonStructure`), never by instantiating a Livewire component —
consistent with the rest of the Inertia-era test suite.

### Deliberately NOT ported (Livewire-reactivity concepts with no Inertia equivalent)

A handful of old test methods assert something intrinsic to Livewire's
live, stateful component model — in-memory property state before any
network round-trip, or DOM-morph/markup mechanics. These have no meaningful
Inertia counterpart (a full-page request framework has no "current draft
form state on the server" to inspect) and their *persisted* counterpart is
what was actually ported above:

| Method | Why not ported |
|---|---|
| `StudentOrientationTest::test_changing_the_level_clears_the_stale_orientation` | Asserts in-memory Livewire property state (`assertSet`) before any save. Persisted behavior is covered by test #3 above. |
| `InscriptionStudentFieldsTest::test_changing_the_level_clears_the_stale_orientation` | Same pattern. |
| `InscriptionStudentFieldsTest::test_mode_select_remains_a_single_native_select_through_conditional_branch_changes` | Asserts rendered Livewire/Blade HTML markup mechanics (a specific `<select>` tag surviving Livewire's DOM morph). Not a business rule — irrelevant once the component is removed. |
| `InscriptionStudentFieldsTest::test_group_select_renders_the_available_group_options_inside_the_livewire_updatable_select` | Same pattern — Blade/Livewire markup mechanics, not business behavior. |
| `EmployeeProfileFieldsTest::test_a_non_image_upload_is_refused_before_saving` | Asserts Livewire's own client-side temporary-upload preview-rejection layer (`FileNotPreviewableException`), which happens before Livewire's own server validation even runs. The second line of defense — the real `image` validation rule — is what a genuine HTTP upload actually exercises, and that IS ported (test #11 above, `test_an_oversized_photo_is_rejected`, plus the existing `test_store_validates_the_full_rule_set`). |
| `CenterScopingTest::test_lists_refresh_when_the_context_changes` | Asserts a live component re-rendering after a `dispatch('context-changed')` event. Inertia pages re-fetch via a full page visit instead; the underlying claim — that a changed context produces different scoped results on the next load — is implicitly proven by every center-scoping test in this document. |

---

## Full pairing table (all Livewire test files with `Livewire::test(...)` calls)

### Clean 1:1 pairings (file → file, whole file replaced)

| Livewire test file | Component(s) tested | Inertia replacement | Verified? |
|---|---|---|---|
| `Students/StudentsCrudTest.php` | `StudentsIndex` | `Students/StudentsInertiaCrudTest.php` | ✅ |
| `Students/StudentOrientationTest.php` | `StudentsIndex` (orientation sub-feature) | `Students/StudentsInertiaCrudTest.php` | ✅ method-level parity confirmed (gap closure #3, #4 above) |
| `Groups/GroupsCrudTest.php` | `GroupsIndex` | `Groups/GroupsInertiaCrudTest.php` | ✅ |
| `Inscriptions/InscriptionsCrudTest.php` | `InscriptionsIndex` | `Inscriptions/InscriptionsInertiaCrudTest.php` | ✅ |
| `Inscriptions/InscriptionStudentFieldsTest.php` | `InscriptionsIndex` (new-student sub-feature) | `Inscriptions/InscriptionsInertiaCrudTest.php` | ✅ method-level parity confirmed (gap closure #5-#8 above; 2 methods deliberately not ported, see table above) |
| `People/EmployeesCrudTest.php` | `EmployeesIndex` | `People/EmployeesInertiaCrudTest.php` | ✅ |
| `People/EmployeeProfileFieldsTest.php` | `EmployeesIndex` (photo/address sub-feature) | `People/EmployeesInertiaCrudTest.php` | ✅ method-level parity confirmed (gap closure #9-#11 above; 1 method deliberately not ported) |
| `People/UsersCrudTest.php` | `UsersIndex` | `Inertia/UsersInertiaTest.php` | ✅ |
| `Authorization/UserAuthorizationTest.php` | `ManageAuthorization` | `Inertia/UsersInertiaTest.php` (same file — authorization test methods) | ✅ method-name parity confirmed |
| `Authorization/RoleManagementLivewireTest.php` | `RolesIndex`, `RoleForm` | `Inertia/RolesInertiaTest.php` | ✅ method-for-method parity confirmed |
| `Finance/CaissesCrudTest.php` | `CaissesIndex` | `Finance/CaissesInertiaCrudTest.php` | ✅ |
| `Finance/CaisseManagementPageTest.php` | `CaisseJournal`, tabbed-page access | `Finance/CaissesInertiaCrudTest.php` | ✅ method-level parity confirmed (gap closure #12-#14 above) |
| `Finance/CaisseTransfersTest.php` | `CaisseTransfersIndex` | `Finance/CaisseTransfersInertiaCrudTest.php` | ✅ |
| `Finance/EncaissementsCrudTest.php` | `EncaissementsIndex` | `Finance/EncaissementsInertiaCrudTest.php` | ✅ |
| `Finance/DepensesCrudTest.php` | `DepensesIndex` | `Finance/DepensesInertiaCrudTest.php` | ✅ |
| `Finance/RemboursementsCrudTest.php` | `RemboursementsIndex` | `Finance/RemboursementsInertiaCrudTest.php` | ✅ |
| `Context/DashboardStatsTest.php` | `DashboardStats` | `Inertia/DashboardInertiaTest.php` | ✅ |
| `Context/CenterScopingTest.php` | `StudentsIndex`, `EmployeesIndex`, `GroupsIndex`, `InscriptionsIndex`, `SallesTab`, `UsersIndex` (cross-cutting) | Spread across `Students/StudentsInertiaCrudTest.php`, `People/EmployeesInertiaCrudTest.php`, `Groups/GroupsInertiaCrudTest.php`, `Inscriptions/InscriptionsInertiaCrudTest.php`, `Settings/SettingsTest.php`, `Inertia/UsersInertiaTest.php` | ✅ all 7 scenarios covered (5 pre-existing + 2 added, gap closure #1-#2 above; the 7th is the deliberately-not-ported reactivity test) |

**All 18 files above are now fully cleared for deletion in Phase 11D.**

### Already-migrated files (comment-only Livewire mentions, not part of the deletion set)

| File | Note |
|---|---|
| `Finance/TypesDepensesCrudTest.php` | Docblock mentions Livewire historically; zero actual `Livewire::test(` calls. Already tests the live `TypeDepenseController`. Not a deletion candidate — it already IS the Inertia-era test. |
| `Settings/SettingsTest.php` | Same pattern — already tests `SettingController`. Not a deletion candidate (this is the file the Salles center-scoping test was added to above). |

### Mixed files — method-level edits required, file must be KEPT (never wholesale deleted)

| File | Livewire-dependent methods (delete/rewrite in Phase 11D) | Non-Livewire methods (MUST stay) | Coverage confirmed? |
|---|---|---|---|
| `Authorization/SuperAdminProtectionTest.php` | `test_the_last_super_admin_cannot_lose_the_role`, `test_a_super_admin_can_be_demoted_when_another_remains` | `test_gate_before_grants_abilities_only_to_super_admins`, `test_assign_super_admin_command_assigns_the_role`, `test_assign_super_admin_command_fails_for_unknown_email`, `test_non_super_admin_users_do_not_bypass_unknown_abilities` — test `Gate::before` and the `auth:assign-super-admin` Artisan command directly, unrelated to Livewire; these stay in the file permanently. | ✅ Both Livewire-dependent methods now ported (gap closure #15-#16 above) — the 2 methods may be safely removed from this file in Phase 11D. |
| `Context/CurrentContextTest.php` | `test_switcher_component_changes_year_and_dispatches_event`, `test_switcher_component_changes_center`, `test_center_scoped_user_switcher_cannot_change_center` | `test_defaults_to_the_default_academic_year`, `test_year_can_be_switched_and_persists_in_session`, `test_global_user_can_switch_center_and_select_all`, `test_center_scoped_user_is_locked_to_their_center` — test the framework-agnostic `CurrentContext` service directly; these stay in the file permanently. | ✅ Already confirmed covered by `Inertia/ContextUpdateTest.php`'s equivalent `POST /backoffice/context` scenarios — no new tests needed, the 3 Livewire-dependent methods may be removed in Phase 11D. |

### Special case — a test that directly instantiates a to-be-deleted Livewire component

`tests/Feature/Backoffice/Inertia/ContextUpdateTest.php::
test_context_change_through_the_new_endpoint_is_observed_by_a_legacy_
livewire_page` calls `Livewire::test(StudentsIndex::class)` directly as a
cross-check. This one method must be removed (not the whole file — every
other method in it is a legitimate Inertia test) as part of the Students
deletion group in Phase 11D, at the same time `StudentsIndex` itself is
deleted.

### Non-Livewire files, confusingly similar names (not part of the deletion set)

`Authorization/RoleManagementAuthorizationTest.php` — zero `Livewire::test(`
calls, a plain HTTP/route-level authorization test already hitting the live
`roles.*` routes. Not part of the deletion set.

---

## Summary: readiness for Phase 11D deletion, by test file

| Status | Files |
|---|---|
| **Fully cleared for deletion** — clean pairing, coverage confirmed | All 18 files in the pairing table above (13 originally clean 1:1 pairings + `CenterScopingTest.php` + the 4 sub-feature files, all now method-level verified). |
| **Never delete whole file — remove only the named Livewire-dependent methods** | `Authorization/SuperAdminProtectionTest.php` (remove 2 methods), `Context/CurrentContextTest.php` (remove 3 methods) — both files stay, permanently, for their non-Livewire coverage. |
| **Edit one method, keep the file** | `Inertia/ContextUpdateTest.php` (remove the one `StudentsIndex`-dependent method when Students is deleted). |
| **Not part of the deletion set at all** | `Finance/TypesDepensesCrudTest.php`, `Settings/SettingsTest.php`, `Authorization/RoleManagementAuthorizationTest.php`. |

**No open coverage-verification items remain.** Phase 11C (dependency graph
classification) and Phase 11D (module-by-module deletion) may proceed for
every module without further test-coverage stop conditions.
