<?php $page = 'todo'; ?>
@extends('layout.mainlayout')
@section('content')

<div class="page-wrapper notes-page-wrapper">
    <div class="content pb-4">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3 pb-3 border-bottom position-relative">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Todo
                </h3>
                 <nav>
                       <ol class="breadcrumb mb-0">
                          <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                          </li>
                          <li class="breadcrumb-item">
                            Application
                          </li>
                          <li class="breadcrumb-item active" aria-current="page">
                            Todo
                        </li>
                    </ol>
                  </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                  <div class="pe-1 mb-2">
                       <a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                         <i class="ti ti-refresh"></i>
                    </a>
                </div>
                  <div class="pe-1 mb-2">
                    <button type="button" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print" data-bs-original-title="Print">
                         <i class="ti ti-printer"></i>
                    </button>
                 </div>    
                <div class="dropdown me-2 mb-2">
                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center" data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-2"></i>Export
                    </a>
                    <ul class="dropdown-menu  dropdown-menu-end p-3">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-2"></i>Export as PDF</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-2"></i>Export as Excel </a>
                        </li>
                    </ul>	
                </div>                  
                  <div class="mb-2">
                    <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#note-units"><i class="ti ti-square-rounded-plus me-2"></i>Add Task</a>
                  </div>
            </div>
            <a id="toggle_btn2" class="notes-tog position-absolute start-0 avatar avatar-sm rounded-circle bg-primary text-white" href="javascript:void(0);">
                <i class="fas fa-chevron-left"></i>
            </a>
        </div>
        <div class="row">
            <div class="col-xl-3 col-md-12 sidebars-right theiaStickySidebar section-bulk-widget">
                <div class="border rounded-3 mt-4 bg-white p-3">
                    <div class="mb-3 pb-3 border-bottom">
                        <h4 class="d-flex align-items-center"><i class="ti ti-file-text me-2"></i>Todo List</h4>
                    </div>
                    <div class="border-bottom pb-3 ">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="d-flex text-start align-items-center fw-semibold fs-15 nav-link active mb-1" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="true"><i class="ti ti-inbox me-2"></i>Inbox <span class="ms-2">1</span></button>
                            <button class="d-flex text-start align-items-center fw-semibold fs-15 nav-link mb-1" id="v-pills-home-tab" data-bs-toggle="pill" data-bs-target="#v-pills-home" type="button" role="tab" aria-controls="v-pills-home" aria-selected="false"><i class="ti ti-circle-check me-2"></i>Done</button>
                            <button class="d-flex text-start align-items-center fw-semibold fs-15 nav-link mb-1" id="v-pills-messages-tab" data-bs-toggle="pill" data-bs-target="#v-pills-messages" type="button" role="tab" aria-controls="v-pills-messages" aria-selected="false"><i class="ti ti-star me-2"></i>Important</button>
                            <button class="d-flex text-start align-items-center fw-semibold fs-15 nav-link mb-0" id="v-pills-settings-tab" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false"><i class="ti ti-trash me-2"></i>Trash</button>
                          </div>
                    </div>
                    <div class="mt-3">
                        <div class="border-bottom px-2 pb-3 mb-3">
                            <h5 class="mb-2">Tags</h5>
                            <div class="d-flex flex-column mt-2">
                                <a href="javascript:void(0);" class="text-info mb-2"><span class="text-info me-2"><i class="fas fa-square square-rotate fs-10"></i></span>Pending</a>
                                <a href="javascript:void(0);" class="text-danger mb-2"><span class="text-danger me-2"><i class="fas fa-square square-rotate fs-10"></i></span>Onhold</a>
                                <a href="javascript:void(0);" class="text-warning mb-2"><span class="text-warning me-2"><i class="fas fa-square square-rotate fs-10"></i></span>Inprogress</a>
                                <a href="javascript:void(0);" class="text-success"><span class="text-success me-2"><i class="fas fa-square square-rotate fs-10"></i></span>Done</a>
                            </div>
                        </div>
                        <div class="px-2">
                            <h5 class="mb-2">Priority</h5>
                            <div class="d-flex flex-column mt-2">
                                <a href="javascript:void(0);" class="text-warning mb-2"><span class="text-warning me-2"><i class="fas fa-square square-rotate fs-10"></i></span>Medium</a>
                                <a href="javascript:void(0);" class="text-success mb-2"><span class="text-success me-2"><i class="fas fa-square square-rotate fs-10"></i></span>High</a>
                                <a href="javascript:void(0);" class="text-danger"><span class="text-danger me-2"><i class="fas fa-square square-rotate fs-10"></i></span>Low</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9 budget-role-notes">
                <div class="bg-white rounded-3 d-flex align-items-center justify-content-between flex-wrap my-4 p-3 pb-0">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <select class="select">
                                <option>Bulk Actions</option>
                                <option>Delete Marked</option>
                                <option>Unmark All</option>
                                <option>Mark All</option>
                            </select>
                        </div>
                        <a href="#" class="btn btn-light">Apply</a>
                    </div>
                    <div class="form-sort mb-3">
                        <i class="ti ti-filter feather-filter info-img"></i>
                        <select class="select">
                            <option>Recent</option>
                            <option>Last Modified</option>
                            <option>Last Modified by me</option>
                        </select>
                    </div>
                </div>
                <div class="tab-content" id="v-pills-tabContent">
                    <div class="tab-pane fade active show" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">	
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="accordion todo-accordion" id="accordionExample">
                                    <div class="accordion-item">
                                        <div class="accordion-header" id="headingOne">
                                            <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"  aria-controls="collapseOne">
                                                <div class="d-flex align-items-center justify-content-between w-100 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <span><i class="ti ti-calendar-due me-2"></i></span>
                                                        <h5 class="fw-semibold">Today</h5>
                                                        <span class="avatar avatar-xs bg-primary rounded-circle p-1 ms-2">1</span>
                                                    </div>  
                                                    <div>
                                                        <a href="javascript:void(0);">
                                                            <span><i class="fas fa-chevron-down"></i></span>
                                                        </a>
                                                    </div>
                                                </div> 
                                            </div>
                                        </div>
                                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                                <div class="card">
                                                    <div class="card-body p-3 pb-0">
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                            <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                                                <div class="form-check form-check-md me-2">
                                                                    <input class="form-check-input" type="checkbox">
                                                                </div>
                                                                <div class="strike-info">
                                                                    <h4 class="mb-1">Meeting with Shaun Park at 4:50pm</h4>
                                                                    <p>Discuss about new project</p>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                                                <div class="notes-card-body d-flex align-items-center">
                                                                    <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i> High</p>
                                                                    <p class="badge bg-outline-secondary mb-0"> New</p>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="avatar avatar-md me-2"><img src="./build/img/users/user-24.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                        <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                </div>	
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion todo-accordion" id="accordionExample2">
                                    <div class="accordion-item">
                                        <div class="accordion-header" id="headingTwo">
                                            <div class="accordion-button"  data-bs-toggle="collapse" data-bs-target="#collapseTwo"  aria-controls="collapseTwo">
                                                <div class="d-flex align-items-center justify-content-between w-100 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <span><i class="ti ti-calendar-due me-2"></i></span>
                                                        <h5 class="fw-semibold">Yesterday</h5>
                                                    </div>  
                                                    <div>
                                                        <a href="javascript:void(0);">
                                                            <span><i class="fas fa-chevron-down"></i></span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo" data-bs-parent="#accordionExample2">
                                            <div class="accordion-body">
                                                <div class="card">
                                                    <div class="card-body p-3 pb-0">
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                            <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                                                <div class="form-check form-check-md me-2">
                                                                    <input class="form-check-input" type="checkbox">
                                                                </div>
                                                                <div class="strike-info">
                                                                    <h4 class="mb-1">Team meet at Starbucks</h4>
                                                                    <p>Discuss about new project</p>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                                                <div class="notes-card-body d-flex align-items-center">
                                                                    <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                                    <p class="badge bg-outline-info mb-0">Pending</p>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <span><i class="ti ti-star me-2"></i></span>
                                                                    <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-02.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                        <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                </div>	
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion todo-accordion" id="accordionExample4">
                                    <div class="accordion-item">
                                        <div class="accordion-header" id="headingFour">
                                            <div class="accordion-button"  data-bs-toggle="collapse" data-bs-target="#collapseFour"  aria-controls="collapseFour">
                                                <div class="d-flex align-items-center justify-content-between w-100 mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <span><i class="ti ti-calendar-due me-2"></i></span>
                                                        <h5 class="fw-semibold">25/07/2023</h5>
                                                    </div>  
                                                    <div>
                                                        <a href="javascript:void(0);">
                                                            <span><i class="fas fa-chevron-down"></i></span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="collapseFour" class="accordion-collapse collapse show" aria-labelledby="headingFour" data-bs-parent="#accordionExample4">
                                            <div class="accordion-body">
                                                <div class="card mb-3">
                                                    <div class="card-body p-3 pb-0">
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                            <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                                                <div class="form-check form-check-md me-2">
                                                                    <input class="form-check-input" type="checkbox">
                                                                </div>
                                                                <div class="strike-info">
                                                                    <h4 class="mb-1">New User Registered</h4>
                                                                    <p>Add new user</p>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                                                <div class="notes-card-body d-flex align-items-center">
                                                                    <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                                    <p class="badge bg-outline-info mb-0">Pending</p>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <span><i class="ti ti-star me-2"></i></span>
                                                                    <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-03.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                        <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                </div>	
                                                <div class="card">
                                                    <div class="card-body p-3 pb-0">
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                                                            <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3 todo-strike-content">
                                                                <div class="form-check form-check-md me-2">
                                                                    <input class="form-check-input" type="checkbox">
                                                                </div>
                                                                <div class="strike-info">
                                                                    <h4 class="mb-1">Download Complete</h4>
                                                                    <p>Install console machines and prerequiste softwares</p>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                                                <div class="notes-card-body d-flex align-items-center">
                                                                    <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                                    <p class="badge bg-outline-warning mb-0">InProgress</p>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <span><i class="ti ti-star me-2"></i></span>
                                                                    <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-04.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                        <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                </div>	
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>										                                             
                        </div>
                    </div>
                    <div class="tab-pane fade " id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                        <div class="d-block">
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Team meet at Starbucks</h4>
                                                <p>Discuss about new project</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                <p class="badge bg-outline-info mb-0">Pending</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span><i class="ti ti-star me-2"></i></span>
                                                <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-02.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Meeting with Shaun Park at 4:50pm</h4>
                                                <p>Discuss about new project</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i> High</p>
                                                <p class="badge bg-outline-secondary mb-0"> New</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-md me-2"><img src="./build/img/users/user-24.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            <div class="card mb-3">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">New User Registered</h4>
                                                <p>Add new user</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                <p class="badge bg-outline-info mb-0">Pending</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span><i class="ti ti-star me-2"></i></span>
                                                <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-03.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab">
                        <div class="d-block">
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Team meet at Starbucks</h4>
                                                <p>Identify the implementation team</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                <p class="badge bg-outline-info mb-0">Pending</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span><i class="ti ti-star me-2"></i></span>
                                                <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-05.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Meet Lisa to discuss project details</h4>
                                                <p>Discuss about additional features</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i> High</p>
                                                <p class="badge bg-outline-secondary mb-0"> New</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-md me-2"><img src="./build/img/users/user-30.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Download Complete</h4>
                                                <p>Install console machines and prerequiste softwares</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                <p class="badge bg-outline-info mb-0">Pending</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span><i class="ti ti-star me-2"></i></span>
                                                <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-03.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                        <div class="d-block">
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Team meet at Starbucks</h4>
                                                <p>Discuss about new project</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                <p class="badge bg-outline-info mb-0">Pending</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span><i class="ti ti-star me-2"></i></span>
                                                <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-07.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                            <div class="card">
                                <div class="card-body p-3 pb-0">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <div class="input-block todo-inbox-check d-flex align-items-center w-50 mb-3">
                                            <div class="form-check form-check-md me-2">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                            <div class="strike-info">
                                                <h4 class="mb-1">Download Complete</h4>
                                                <p>Install console machines and prerequiste softwares</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center flex-fill justify-content-between ms-4 mb-3">
                                            <div class="notes-card-body d-flex align-items-center">
                                                <p class="badge bg-outline-danger d-inline-flex align-items-center me-2 mb-0"><i class="fas fa-circle fs-6 me-1"></i>High</p>
                                                <p class="badge bg-outline-info mb-0">Pending</p>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span><i class="ti ti-star me-2"></i></span>
                                                <span class="avatar avatar-md me-2"><img src="./build/img/profiles/avatar-08.jpg" alt="Img" class="img-fluid rounded-circle"></span>
                                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                    <a href="#" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row custom-pagination">
                    <div class="col-md-12">
                        <div class="paginations d-flex justify-content-end">
                            <span><i class="fas fa-chevron-left"></i></span>
                            <ul class="d-flex align-items-center page-wrap">
                                <li>
                                    <a href="javascript:void(0);" class="active">
                                            1
                                    </a>  
                                </li>
                                <li>
                                    <a href="javascript:void(0);">
                                        2
                                    </a> 
                                </li>
                                <li>
                                    <a href="javascript:void(0);">
                                        3
                                    </a> 
                                </li>
                                <li>
                                    <a href="javascript:void(0);">
                                        4
                                    </a> 
                                </li>
                            </ul>
                            <span><i class="fas fa-chevron-right"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>		
    </div>
</div>
    @component('components.modal-popup')
    @endcomponent
@endsection
