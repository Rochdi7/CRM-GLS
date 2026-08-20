<?php $page = 'ui-list-group'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        List Group
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Basic example</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">The most basic list group is an unordered list with list items and the proper classes. Build upon it with the options that follow, or with your own CSS as needed.</p>
                        
                        <ul class="list-group">
                            <li class="list-group-item">
                                <i class="ti ti-brand-slack me-1 align-middle fs-18"></i> Slack Collaboration
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-nextcloud me-1 align-middle fs-18"></i> Nextcloud File Sharing
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-zoom me-1 align-middle fs-18"></i> Zoom Video Meetings
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-stripe me-1 align-middle fs-18"></i> Stripe Billing System
                            </li>
                        </ul>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Active Items</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add <code>.active</code> to a <code>.list-group-item</code> to indicate the current active selection.</p>
                        
                        <ul class="list-group">
                            <li class="list-group-item active">
                                <i class="ti ti-brand-slack me-1 align-middle fs-18"></i> Slack Collaboration
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-nextcloud me-1 align-middle fs-18"></i> Nextcloud File Sharing
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-zoom me-1 align-middle fs-18"></i> Zoom Video Meetings
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-notion me-1 align-middle fs-18"></i> Notion Workspace
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-stripe me-1 align-middle fs-18"></i> Stripe Billing System
                            </li>
                        </ul>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Disabled Items</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code>.disabled</code> to a <code>.list-group-item</code> to make it
                            <em>appear</em> disabled.
                        </p>
                        
                        <ul class="list-group">
                            <li class="list-group-item disabled" aria-disabled="true">
                                <i class="ti ti-brand-slack me-1 align-middle fs-18"></i> Slack Collaboration
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-nextcloud me-1 align-middle fs-18"></i> Nextcloud File Sharing
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-zoom me-1 align-middle fs-18"></i> Zoom Video Meetings
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-notion me-1 align-middle fs-18"></i> Notion Workspace
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-stripe me-1 align-middle fs-18"></i> Stripe Billing System
                            </li>
                        </ul>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Links and Buttons</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Use <code>&lt;a&gt;</code>s or <code>&lt;button&gt;</code>s to create  <em>actionable</em> list group items with hover, disabled, and active states by adding <code>.list-group-item-action</code>.</p>

                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action active">
                                <i class="ti ti-brand-slack me-1 align-middle fs-18"></i> Slack Collaboration
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="ti ti-brand-nextcloud me-1 align-middle fs-18"></i> Nextcloud File Sharing 
                            </a>
                            <button type="button" class="list-group-item list-group-item-action">
                                <i class="ti ti-brand-zoom me-1 align-middle fs-18"></i> Zoom Video Meetings
                            </button>
                            <button type="button" class="list-group-item list-group-item-action">
                                <i class="ti ti-brand-notion me-1 align-middle fs-18"></i> Notion Workspace
                            </button>
                            <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1">
                                <i class="ti ti-brand-stripe me-1 align-middle fs-18"></i> Stripe Billing System
                            </a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Flush</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add <code>.list-group-flush</code> to remove some borders and rounded corners to render list group items edge-to-edge in a parent container (e.g., cards).</p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><i class="ti ti-brand-slack me-1 align-middle fs-18"></i> Slack Collaboration</li>
                            <li class="list-group-item"><i class="ti ti-brand-nextcloud me-1 align-middle fs-18"></i> Nextcloud File Sharing</li>
                            <li class="list-group-item"><i class="ti ti-brand-zoom me-1 align-middle fs-18"></i> Zoom Video Meetings</li>
                            <li class="list-group-item"><i class="ti ti-brand-notion me-1 align-middle fs-18"></i> Notion Workspace</li>
                            <li class="list-group-item"><i class="ti ti-brand-stripe me-1 align-middle fs-18"></i> Stripe Billing System</li>
                        </ul>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Numbered</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Numbers are generated by <code>counter-reset</code> on the <code>&lt;ol&gt;</code>, and then styled and placed with a <code>::before</code> psuedo-element on the <code>&lt;li&gt;</code> with <code>counter-increment</code> and <code>content</code>. </p>

                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">SmartOne Admin</div>
                                    SmartOne Admin
                                </div>
                                <span class="badge bg-primary rounded-pill">865</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">SmartOne React Admin</div>
                                    SmartOne React Admin
                                </div>
                                <span class="badge bg-primary rounded-pill">140</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Angular Version</div>
                                    Angular Version
                                </div>
                                <span class="badge bg-primary rounded-pill">85</span>
                            </li>
                        </ol>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Contextual Classes</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Use contextual classes to style list items with a stateful background and color.</p>

                        <ul class="list-group">
                            <li class="list-group-item">Dapibus ac facilisis in</li>
                            <li class="list-group-item list-group-item-primary">A simple primary list group item</li>
                            <li class="list-group-item list-group-item-secondary">A simple secondary list group item</li>
                            <li class="list-group-item list-group-item-success">A simple success list group item</li>
                            <li class="list-group-item list-group-item-danger">A simple danger list group item</li>
                            <li class="list-group-item list-group-item-warning">A simple warning list group item</li>
                            <li class="list-group-item list-group-item-info">A simple info list group item</li>
                            <li class="list-group-item list-group-item-light">A simple light list group item</li>
                            <li class="list-group-item list-group-item-dark">A simple dark list group item</li>
                        </ul>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Contextual Classes with Link</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">Use contextual classes to style list items with a stateful background and color.</p>

                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">Darius ac facilities in</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-primary">A simple primary list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-secondary">A simple secondary list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-success">A simple success list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-danger">A simple danger list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-warning">A simple warning list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-info">A simple info list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-light">A simple light list group item</a>
                            <a href="#" class="list-group-item list-group-item-action list-group-item-dark">A simple dark list group item</a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Custom Content</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">Add nearly any HTML within, even for linked list groups like the one below, with the help of flexbox utilities.</p>

                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action active">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="fs-16 mb-1">List group item heading</h5>
                                    <small>3 days ago</small>
                                </div>
                                <p class="mb-1">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
                                <small>Donec id elit non mi porta.</small>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="fs-16 mb-1">List group item heading</h5>
                                    <small class="text-muted">3 days ago</small>
                                </div>
                                <p class="mb-1">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
                                <small class="text-muted">Donec id elit non mi porta.</small>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="fs-16 mb-1">List group item heading</h5>
                                    <small class="text-muted">3 days ago</small>
                                </div>
                                <p class="mb-1">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
                                <small class="text-muted">Donec id elit non mi porta.</small>
                            </a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Horizontal</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code>.list-group-horizontal</code> to change the layout of list group items from vertical to horizontal across all breakpoints. Alternatively, choose a responsive variant <code>.list-group-horizontal-{sm|md|lg|xl|xxl}</code> to make a list group horizontal starting at that breakpoint’s <code>min-width</code>.</p>

                        <ul class="list-group list-group-horizontal mb-3">
                            <li class="list-group-item">
                                <i class="ti ti-brand-slack me-1 align-middle fs-18"></i> Slack
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-zoom me-1 align-middle fs-18"></i> Zoom
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-trello me-1 align-middle fs-18"></i> Trello
                            </li>
                        </ul>

                        <ul class="list-group list-group-horizontal-sm mb-3">
                            <li class="list-group-item">
                                <i class="ti ti-brand-notion me-1 align-middle fs-18"></i> Notion
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-stripe me-1 align-middle fs-18"></i> Stripe
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-box me-1 align-middle fs-18"></i> Dropbox
                            </li>
                        </ul>

                        <ul class="list-group list-group-horizontal-md">
                            <li class="list-group-item">
                                <i class="ti ti-brand-github me-1 align-middle fs-18"></i> GitHub
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-discord me-1 align-middle fs-18"></i> Discord
                            </li>
                            <li class="list-group-item">
                                <i class="ti ti-brand-asana me-1 align-middle fs-18"></i> Asana
                            </li>
                        </ul>

                    </div> <!-- end card body -->							
                </div> <!-- end card -->						
            </div> <!-- end col -->
            
            <div class="col-xl-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">With Badges</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add badges to any list group item to show unread counts, activity, and more with the help of some utilities.</p>

                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                New Task Assignments
                                <span class="badge bg-primary rounded-pill">5</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Upcoming Meetings
                                <span class="badge bg-success rounded-pill">3</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                System Notifications
                                <span class="badge bg-danger rounded-pill">15+</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Completed Projects
                                <span class="badge bg-success rounded-pill">8</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Overdue Tasks
                                <span class="badge bg-warning rounded-pill">2</span>
                            </li>
                        </ul>
                    </div> <!-- end card body -->							
                </div> <!-- end card -->						
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Checkboxes and Radios</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Place Bootstrap’s checkboxes and radios within list group items and customize as needed. You can use them without <code>&lt;label&gt;</code>s, but please remember to include an <code>aria-label</code> attribute and value for accessibility.</p>

                        <ul class="list-group">
                            <li class="list-group-item">
                                <input class="form-check-input me-1" type="checkbox" value="" id="firstCheckbox">
                                <label class="form-check-label" for="firstCheckbox">First checkbox</label>
                            </li>
                            <li class="list-group-item">
                                <input class="form-check-input me-1" type="checkbox" value="" id="secondCheckbox">
                                <label class="form-check-label" for="secondCheckbox">Second checkbox</label>
                            </li>
                        </ul>

                        <ul class="list-group mt-2">
                            <li class="list-group-item">
                                <input class="form-check-input me-1" type="radio" name="listGroupRadio" value="" id="firstRadio" checked>
                                <label class="form-check-label" for="firstRadio">First radio</label>
                            </li>
                            <li class="list-group-item">
                                <input class="form-check-input me-1" type="radio" name="listGroupRadio" value="" id="secondRadio">
                                <label class="form-check-label" for="secondRadio">Second radio</label>
                            </li>
                        </ul>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
</div>
@endsection
