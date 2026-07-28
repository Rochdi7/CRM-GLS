<?php $page = 'tables-basic'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper cardhead">
        <div class="content">

            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col">
                        <h3 class="page-title">Basic Tables</h3>
                    </div>
                </div>
            </div>
            <!-- /Page Header -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Basic Example</h4>
                        </div>
                        <div class="card-body">
                            <p>For basic styling—light padding and only horizontal dividers—add the base class <code>.table</code> to any <code>&lt;table&gt;</code>.</p>    
                            
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 online avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>Mark
                                                </div>
                                            </th>
                                            <td>mark@example.com</td>
                                            <td><span class="badge rounded-pill badge-soft-success">Active</span></td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button type="button" class="drop-arrow-none btn btn-icon btn-outline-light btn-sm border-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Row options">
                                                        <i class="ti ti-dots-vertical fs-18 text-dark"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li><a class="dropdown-item" href="#">Edit</a></li>
                                                        <li><a class="dropdown-item" href="#">Delete</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 online avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>Jacob
                                                </div>
                                            </th>
                                            <td>jacob@example.com</td>
                                            <td><span class="badge rounded-pill badge-soft-danger">Inactive</span></td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button type="button" class="drop-arrow-none btn btn-icon btn-outline-light btn-sm border-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Row options">
                                                        <i class="ti ti-dots-vertical fs-18 text-dark"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li><a class="dropdown-item" href="#">Edit</a></li>
                                                        <li><a class="dropdown-item" href="#">Delete</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 online avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                                    </span>Larry
                                                </div>
                                            </th>
                                            <td>larry@example.com</td>
                                            <td>
                                                <span class="badge rounded-pill badge-soft-success">Active</span>
                                            </td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button type="button" class="drop-arrow-none btn btn-icon btn-outline-light btn-sm border-0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Row options">
                                                        <i class="ti ti-dots-vertical fs-18 text-dark"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li><a class="dropdown-item" href="#">Edit</a></li>
                                                        <li><a class="dropdown-item" href="#">Delete</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Dark Table</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">You can also invert the colors—with light text on dark backgrounds—with <code>.table-dark</code>.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-dark mb-0">

                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 online avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt="img">
                                                    </span>Mark
                                                </div>
                                            </th>
                                            <td>mark@example.com</td>
                                            <td><span class="badge bg-success">Active</span></td>
                                            <td class="text-right">                                                       
                                                <a href="#"><i class="ti ti-pencil text-success font-18"></i></a>
                                                <a href="#"><i class="ti ti-trash text-danger font-18"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 online avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                                                    </span>Jacob
                                                </div>
                                            </th>
                                            <td>jacob@example.com</td>
                                            <td><span class="badge bg-danger">Inactive</span></td>
                                            <td class="text-right">                                                       
                                                <a href="#"><i class="ti ti-pencil text-success font-18"></i></a>
                                                <a href="#"><i class="ti ti-trash text-danger font-18"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 online avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                                    </span>Larry
                                                </div>
                                            </th>
                                            <td>larry@example.com</td>
                                            <td><span class="badge bg-success">Active</span></td>
                                            <td class="text-right">                                                       
                                                <a href="#"><i class="ti ti-pencil text-success font-18"></i></a>
                                                <a href="#"><i class="ti ti-trash text-danger font-18"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Table Head</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Use one of two modifier classes to make <code>&lt;thead&gt;</code>s appear light or dark gray.</p>    
                            
                            <div class="table-responsive">
                                <table class="table mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td><button type="button" class="btn btn-light btn-sm">View</button></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td><button type="button" class="btn btn-light btn-sm">View</button></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td><button type="button" class="btn btn-light btn-sm">View</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Striped Rows</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Use <code>.table-striped</code> to add zebra-striping to any table row within the <code>&lt;tbody&gt;</code>.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>
                                                <button class="btn btn-sm btn-success">
                                                    <i class="ti ti-download me-1"></i>Download
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>
                                                <button class="btn btn-sm btn-success">
                                                    <i class="ti ti-download me-1"></i>Download
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td>
                                                <button class="btn btn-sm btn-success">
                                                    <i class="ti ti-download me-1"></i>Download
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Bordered Table</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Add <code>.table-bordered</code> for borders on all sides of the table and cells.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-success rounded-pill"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-info rounded-pill"><i class="ti ti-edit"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-danger rounded-pill"><i class="ti ti-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-success rounded-pill"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-info rounded-pill"><i class="ti ti-edit"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-danger rounded-pill"><i class="ti ti-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-success rounded-pill"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-info rounded-pill"><i class="ti ti-edit"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-soft-danger rounded-pill"><i class="ti ti-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Borderless Table</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc"> Add <code>.table-borderless</code> for a table without borders.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-borderless mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-light"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-light"><i class="ti ti-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-light"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-light"><i class="ti ti-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-light"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-light"><i class="ti ti-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Table Border Color</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Add <code>.table-bordered</code> & <code>.border-*</code> for colored borders on all sides of the table and cells.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-bordered border-primary mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td scope="row">1</td>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-success"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-info"><i class="ti ti-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td scope="row">2</td>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-success"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-info"><i class="ti ti-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td scope="row">3</td>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td>
                                                <div class="d-inline-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-success"><i class="ti ti-download"></i></a>
                                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-info"><i class="ti ti-edit"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Table Border Color</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Add <code>.table-bordered</code> & <code>.border-*</code> for colored borders on all sides of the table and cells.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-bordered border-success mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td scope="row">1</td>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>
                                                <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light">
                                                    <i class="ti ti-trash me-1"></i>Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td scope="row">2</td>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>
                                                <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light">
                                                    <i class="ti ti-trash me-1"></i>Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td scope="row">3</td>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td>
                                                <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light">
                                                    <i class="ti ti-trash me-1"></i>Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Vertical Alignment</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <p class="card-title-desc">Table cells in <code>&lt;tbody&gt;</code> inherit their alignment from <code>&lt;table&gt;</code> and are aligned to the the top by default. Use the vertical align classes to re-align where needed.</p>
                                
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">

                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>First Name</th>
                                                <th>Last Name</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th scope="row">1</th>
                                                <td>Mark</td>
                                                <td>Otto</td>
                                                <td>
                                                    <button type="button" class="btn btn-light btn-sm">View</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">2</th>
                                                <td>Jacob</td>
                                                <td>Thornton</td>
                                                <td>
                                                    <button type="button" class="btn btn-light btn-sm">View</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">3</th>
                                                <td>Larry</td>
                                                <td>Dooley</td>
                                                <td>
                                                    <button type="button" class="btn btn-light btn-sm">View</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">4</th>
                                                <td>Jacob</td>
                                                <td>Thornton</td>
                                                <td>
                                                    <button type="button" class="btn btn-light btn-sm">View</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                            </div>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Nesting</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc"> Border styles, active styles, and table variants are not inherited by nested tables.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">First</th>
                                            <th scope="col">Last</th>
                                            <th scope="col">Handle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>@mdo</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4">
                                            <table class="table mb-0">
                                                <thead>
                                                <tr>
                                                    <th scope="col">Header</th>
                                                    <th scope="col">Header</th>
                                                    <th scope="col">Header</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <th scope="row">A</th>
                                                    <td>First</td>
                                                    <td>Last</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">B</th>
                                                    <td>First</td>
                                                    <td>Last</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>@fat</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">

                <div class="col-xl-6">
                    <div class="card card-h-100">
                        <div class="card-header">
                            <h4 class="card-title">Hoverable Rows</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Add <code>.table-hover</code> to enable a hover state on table rows within a <code>&lt;tbody&gt;</code>.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">

                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-trash"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Small Table</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc"> Add <code>.table-sm</code> to make tables more compact by cutting cell padding in half.</p>    
                            
                            <div class="table-responsive">
                                <table class="table table-sm m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-settings"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-settings"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-settings"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">4</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td class="text-center text-muted">
                                                <a href="javascript: void(0);" class="link-reset fs-20 p-1"> <i class="ti ti-settings"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <!-- start row -->
            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Contextual Classes</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">Use contextual classes to color table rows or individual cells.</p>    
                            
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Column heading</th>
                                            <th>Column heading</th>
                                            <th>Column heading</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                        </tr>

                                        <tr class="table-success">
                                            <th scope="row">2</th>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                        </tr>

                                        <tr class="table-info">
                                            <th scope="row">3</th>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                        </tr>

                                        <tr class="table-warning">
                                            <th scope="row">4</th>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                        </tr>

                                        <tr class="table-danger">
                                            <th scope="row">5</th>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                            <td>Column content</td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>

                            </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Captions</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">A <code>&lt;caption&gt;</code> functions like a heading for a table. It helps users with screen readers to find a table and understand what it’s about and decide if they want to read it.</p>    
                            
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <caption class="pb-0">List of users</caption>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Handle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Mark</td>
                                            <td>Otto</td>
                                            <td>@mdo</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Jacob</td>
                                            <td>Thornton</td>
                                            <td>@fat</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Larry</td>
                                            <td>Dooley</td>
                                            <td>@twitter</td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->
            
            <!-- start row -->
            <div class="row">

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Responsive Table</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-title-desc">
                                Create responsive tables by wrapping any <code>.table</code> in <code>.table-responsive</code> to make them scroll horizontally on small devices (under 768px).
                            </p>
                            
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td><h6 class="mb-0 fs-14 fw-semibold"> #INV368967</h6></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-md me-2 avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="img">
                                                    </div>
                                                    <h6 class="mb-0 fs-14 fw-semibold">Laura Biding</h6>
                                                </div>
                                            </td>
                                            <td>13 May 2025</td>
                                            <td><span class="fw-semibold">$950</span></td>
                                            <td><h6 class="mb-0 fs-14 fw-semibold">Cash on Delivery</h6></td>
                                            <td><span class="badge badge-boxed  badge-outline-warning">Pending</span></td>
                                            <td>
                                                <a href="javascript: void(0);" class="link-reset fs-18 p-1"> <i class="ti ti-pencil"></i></a>
                                                <a href="javascript: void(0);" class="link-reset fs-18 p-1"> <i class="ti ti-trash"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td><h6 class="mb-0 fs-14 fw-semibold"> #INV368967</h6></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-md me-2 avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="img">
                                                    </div>
                                                    <h6 class="mb-0 fs-14 fw-semibold">Justin Gaethje</h6>
                                                </div>
                                            </td>
                                            <td>13 May 2025</td>
                                            <td><span class="fw-semibold">$950</span></td>
                                            <td><h6 class="mb-0 fs-14 fw-semibold">Online Payment</h6></td>
                                            <td><span class="badge badge-outline-danger">Cancelled</span></td>
                                            <td>
                                                <a href="javascript: void(0);" class="link-reset fs-18 p-1"> <i class="ti ti-pencil"></i></a>
                                                <a href="javascript: void(0);" class="link-reset fs-18 p-1"> <i class="ti ti-trash"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td><h6 class="mb-0 fs-14 fw-semibold"> #INV368967</h6></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-md me-2 avatar-rounded">
                                                        <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" alt="img">
                                                    </div>
                                                    <h6 class="mb-0 fs-14 fw-semibold">Simon Cohen</h6>
                                                </div>
                                            </td>
                                            <td>13 May 2025</td>
                                            <td><span class="fw-semibold">$950</span></td>
                                            <td><h6 class="mb-0 fs-14 fw-semibold">Cheque</h6></td>
                                            <td><span class="badge badge-outline-success">Completed</span></td>
                                            <td>
                                                <a href="javascript: void(0);" class="link-reset fs-18 p-1"> <i class="ti ti-pencil"></i></a>
                                                <a href="javascript: void(0);" class="link-reset fs-18 p-1"> <i class="ti ti-trash"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->

            </div>
            <!-- end row -->
                
        </div>
    </div>
@endsection
