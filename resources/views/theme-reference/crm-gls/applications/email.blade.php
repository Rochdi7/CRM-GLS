<?php $page = 'email'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="mb-1">Inbox</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            Application
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Inbox</li>
                    </ol>
                </nav>
            </div>					
        </div>
        <!-- /Page Header -->
         
        <div class="row">

            <!-- Inbox Menu -->
            <div class="col-lg-3 col-md-12">
                <div class="mb-4">
                    <a href="javascript:void(0);" class="btn btn-primary w-100">
                        Compose
                    </a>
                </div>
                <div class="list-group list-group-flush mb-4">
                    <a href="#" class="list-group-item list-group-item-action active"><i class="ti ti-download me-2"></i>Inbox (5)</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="ti ti-star me-2"></i>Important</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="ti ti-send me-2"></i>Sent Mail</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="ti ti-file-database me-2"></i>Drafts</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="ti ti-trash me-2"></i>Drafts</a>
                </div>
            </div>
            <!-- /Inbox Menu -->

            <!-- Inbox -->
            <div class="col-lg-9 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="email-header">
                            <div class="row">
                                <div class="col-xl-9 col-sm-12">
                                    <div class="float-left">
                                        <div class="btn-group me-1 mb-2">
                                            <button type="button" class="btn btn-outline-light dropdown-toggle"
                                                data-bs-toggle="dropdown">Select</button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);">All</a>
                                                <a class="dropdown-item" href="javascript:void(0);">None</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="javascript:void(0);">Read</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Unread</a>
                                            </div>
                                        </div>
                                        <div class="btn-group me-1 mb-2">
                                            <button type="button" class="btn btn-outline-light dropdown-toggle"
                                                data-bs-toggle="dropdown">Actions</button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);">Reply</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Forward</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Archive</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="javascript:void(0);">Mark As
                                                    Read</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Mark As
                                                    Unread</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                            </div>
                                        </div>
                                        <div class="btn-group me-1 mb-2">
                                            <button type="button" class="btn btn-outline-light dropdown-toggle"
                                                data-bs-toggle="dropdown"><i class="fas fa-folder"></i></button>
                                            <div role="menu" class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);">Social</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Forums</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Updates</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="javascript:void(0);">Spam</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Trash</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="javascript:void(0);">New</a>
                                            </div>
                                        </div>
                                        <div class="btn-group me-1 mb-2">
                                            <button type="button" data-bs-toggle="dropdown"
                                                class="btn btn-outline-light dropdown-toggle"><i
                                                    class="fas fa-tags"></i></button>
                                            <div role="menu" class="dropdown-menu">
                                                <a class="dropdown-item" href="javascript:void(0);">Work</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Family</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Social</a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="javascript:void(0);">Primary</a>
                                                <a class="dropdown-item"
                                                    href="javascript:void(0);">Promotions</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Forums</a>
                                            </div>
                                        </div>
                                        <div class="btn-group mb-2">
                                            <input type="text" placeholder="Search Messages" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-sm-12">
                                    <div class="text-xl-end ">
                                        <button type="button" title="Refresh" data-toggle="tooltip"
                                            class="btn btn-outline-light d-none d-md-inline-block me-1  mb-2"><i
                                                class="fas fa-sync-alt"></i></button>
                                        <div class="btn-group  mb-2">
                                            <a class="btn btn-white"><i class="fas fa-angle-left"></i></a>
                                            <a class="btn btn-white"><i class="fas fa-angle-right"></i></a>
                                        </div>
                                        <span class="text-muted d-none d-md-inline-block mb-2">Showing 10 of 112
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="email-content">
                            <div class="table-responsive">
                                <table class="table table-inbox table-hover">
                                    <thead>
                                        <tr>
                                            <th colspan="6">
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox" id="select-all">
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i
                                                        class="fas fa-star starred"></i></span></td>
                                            <td class="text-dark fw-semibold">John Doe</td>
                                            <td  class="text-dark fw-semibold">Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td><i class="fas fa-paperclip"></i></td>
                                            <td class="text-dark fw-semibold">13:14</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td class="text-dark fw-semibold">Envato Account</td>
                                            <td  class="text-dark fw-semibold">Important account security update from Envato
                                            </td>
                                            <td></td>
                                            <td class="text-dark fw-semibold">8:42</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td>Twitter</td>
                                            <td>HRMS Bootstrap Admin Template</td>
                                            <td></td>
                                            <td>30 Nov</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td>Richard Parker</td>
                                            <td >Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td></td>
                                            <td>18 Sep</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td>John Smith</td>
                                            <td>Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td></td>
                                            <td>21 Aug</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td>me, Robert Smith (3)</td>
                                            <td >Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td></td>
                                            <td>1 Aug</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td class="text-dark fw-semibold">Codecanyon</td>
                                            <td class="text-dark fw-semibold">Welcome To Codecanyon</td>
                                            <td></td>
                                            <td  class="text-dark fw-semibold">Jul 13</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td>Richard Miles</td>
                                            <td>Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td><i class="fas fa-paperclip"></i></td>
                                            <td>May 14</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i class="far fa-star"></i></span>
                                            </td>
                                            <td class="text-dark fw-semibold">John Smith</td>
                                            <td  class="text-dark fw-semibold">Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td></td>
                                            <td class="text-dark fw-semibold">11/11/16</td>
                                        </tr>
                                        <tr class="clickable-row">
                                            <td>
                                                <div class="form-check form-check-md">
                                                    <input class="form-check-input" type="checkbox">
                                                </div>
                                            </td>
                                            <td><span class="mail-important"><i
                                                        class="far fa-star starred"></i></span></td>
                                            <td class="text-dark fw-semibold">Mike Litorus</td>
                                            <td  class="text-dark fw-semibold">Lorem ipsum dolor sit amet, consectetuer
                                                adipiscing elit</td>
                                            <td></td>
                                            <td class="text-dark fw-semibold">10/31/16</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Inbox -->

        </div>
    </div>
</div>
<!-- /Page Wrapper -->
@endsection
