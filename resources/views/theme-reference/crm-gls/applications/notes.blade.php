<?php $page = 'notes'; ?>
@extends('layout.mainlayout')
@section('content')
	<!-- Page wrapper -->
    <div class="page-wrapper">
        <div class="content pb-4">
            <div class="d-md-flex d-block align-items-center justify-content-between mb-3 pb-3 border-bottom position-relative">
                <div class="my-auto mb-2">
                    <h3 class="page-title mb-1">Notes</h3>
                     <nav>
                           <ol class="breadcrumb mb-0">
                              <li class="breadcrumb-item">
                                <a href="javascript:void(0);">Dashboard</a>
                              </li>
                              <li class="breadcrumb-item">
                                Application
                              </li>
                              <li class="breadcrumb-item active" aria-current="page">Notes</li>
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
                        <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#note-units"><i class="ti ti-square-rounded-plus me-2"></i>Add Notes</a>
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
                                <button class="d-flex text-start align-items-center fw-semibold fs-15 nav-link active mb-1" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="true"><i class="ti ti-inbox me-2"></i>All Notes<span class="ms-2">1</span></button>
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
                            <div class="border-bottom mb-4 pb-4">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                            <div class="d-flex align-items-center mb-3">
                                                <h4>Important Notes </h4>
                                                <div class="owl-nav slide-nav5 text-end nav-control ms-3"></div>
                                            </div>  
                                            <div class="notes-close mb-3">
                                                <a href="javascript:void(0);" class="text-danger fs-15"><i class="fas fa-times me-1"></i> Close </a>
                                            </div>
                                        </div> 
                                    </div>
                                    <div class="col-md-12">
                                        <div class="notes-slider owl-carousel">
                                            <div class="card rounded-3 mb-0">
                                                <div class="card-body p-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="badge bg-outline-warning d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Medium</span>
                                                        <div>
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </a>
                                                            <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                            </div>
                                                        </div>															
                                                    </div>
                                                    <div class="my-3">
                                                        <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Plan a trip to another country</a></h5>
                                                        <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>20 Jan 2024</p>
                                                        <p class="text-truncate line-clamb-2 text-wrap">Space, the final frontier. These are the voyages of the Starship Enterprise.</p>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                                <img src="./build/img/profiles/avatar-01.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                            </a>	
                                                            <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="me-2">
                                                                <span><i class="fas fa-star text-warning"></i></span>
                                                            </a>
                                                            <a href="javascript:void(0);">
                                                                <span><i class="ti ti-trash text-danger"></i></span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card rounded-3 mb-0">
                                                <div class="card-body p-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                        <div>
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </a>
                                                            <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                            </div>
                                                        </div>															
                                                    </div>
                                                    <div class="my-3">
                                                        <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Improve touch typing</a></h5>
                                                        <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>22 Jan 2024</p>
                                                        <p class="text-truncate line-clamb-2 text-wrap">Well, the way they make shows is, they make one show.</p>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                                <img src="./build/img/profiles/avatar-02.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                            </a>	
                                                            <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="me-2">
                                                                <span><i class="fas fa-star text-warning"></i></span>
                                                            </a>
                                                            <a href="javascript:void(0);">
                                                                <span><i class="ti ti-trash text-danger"></i></span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>	
                                            <div class="card rounded-3 mb-0">
                                                <div class="card-body p-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                        <div>
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </a>
                                                            <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                            </div>
                                                        </div>															
                                                    </div>
                                                    <div class="my-3">
                                                        <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Learn calligraphy</a></h5>
                                                        <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>24 Jan 2024</p>
                                                        <p class="text-truncate line-clamb-2 text-wrap">Calligraphy, the art of beautiful handwriting. The term may derive from the Greek words. </p>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                                <img src="./build/img/profiles/avatar-03.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                            </a>	
                                                            <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Social</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="me-2">
                                                                <span><i class="fas fa-star text-warning"></i></span>
                                                            </a>
                                                            <a href="javascript:void(0);">
                                                                <span><i class="ti ti-trash text-danger"></i></span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card rounded-3 mb-0">
                                                <div class="card-body p-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="badge bg-outline-warning d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Medium</span>
                                                        <div>
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </a>
                                                            <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                            </div>
                                                        </div>															
                                                    </div>
                                                    <div class="my-3">
                                                        <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Plan a trip to another country</a></h5>
                                                        <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>25 Jan 2024</p>
                                                        <p class="text-truncate line-clamb-2 text-wrap">Space, the final frontier. These are the voyages of the Starship Enterprise.</p>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                                <img src="./build/img/profiles/avatar-01.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                            </a>	
                                                            <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="me-2">
                                                                <span><i class="fas fa-star text-warning"></i></span>
                                                            </a>
                                                            <a href="javascript:void(0);">
                                                                <span><i class="ti ti-trash text-danger"></i></span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card rounded-3 mb-0">
                                                <div class="card-body p-4">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                        <div>
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </a>
                                                            <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                                <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                            </div>
                                                        </div>															
                                                    </div>
                                                    <div class="my-3">
                                                        <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Improve touch typing</a></h5>
                                                        <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>26 Jan 2024</p>
                                                        <p class="text-truncate line-clamb-2 text-wrap">Well, the way they make shows is, they make one show.</p>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                                <img src="./build/img/profiles/avatar-02.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                            </a>	
                                                            <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0);" class="me-2">
                                                                <span><i class="fas fa-star text-warning"></i></span>
                                                            </a>
                                                            <a href="javascript:void(0);">
                                                                <span><i class="ti ti-trash text-danger"></i></span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>									
                                        </div>
                                    </div>                                                     
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-success d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Backup Files EOD</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>20 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Project files should be took backup before end of the day.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-05.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>			
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Download Server Logs</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>25 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Server log is a text document that contains a record of all activity.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-06.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>			
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-warning d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Team meet at Starbucks</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>26 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Meeting all teamets at Starbucks for identifying them all.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-07.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-warning d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>	
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-success d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Create a compost pile</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>27 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Compost pile refers to fruit and vegetable scraps, used tea, coffee grounds etc..</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-08.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-warning d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Take a hike at a local park</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Hiking involves a long energetic walk in a natural environment.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-09.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-info d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Research a topic interested</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Research a topic interested by listen actively and attentively.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-10.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab">
                            <div class="row">
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-success d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Backup Files EOD</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>20 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Project files should be took backup before end of the day.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-05.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>			
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Download Server Logs</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>25 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Server log is a text document that contains a record of all activity.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-06.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>			
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-warning d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Team meet at Starbucks</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>26 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Meeting all teamets at Starbucks for identifying them all.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-07.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-warning d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>	
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-success d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Create a compost pile</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>27 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Compost pile refers to fruit and vegetable scraps, used tea, coffee grounds etc..</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-08.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-warning d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Take a hike at a local park</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Hiking involves a long energetic walk in a natural environment.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-09.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-info d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Research a topic interested</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Research a topic interested by listen actively and attentively.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-10.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                            <div class="row">
                                <div class="col-12 d-flex align-items-center justify-content-end">
                                    <a href="#" class="btn btn-danger mb-4">
                                        <span> <i class="ti ti-trash f-20 me-2"></i>  </span>
                                        Restore all
                                    </a>
                                </div>
                            </div>
                            <div class="row">	
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-success d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>High</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Create a compost pile</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>27 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Compost pile refers to fruit and vegetable scraps, used tea, coffee grounds etc..</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-08.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-warning d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Social</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-danger d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>Low</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Take a hike at a local park</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Hiking involves a long energetic walk in a natural environment.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-09.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-info d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Personal</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex">
                                    <div class="card rounded-3 mb-4 flex-fill">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="badge bg-outline-info d-inline-flex align-items-center"><i class="fas fa-circle fs-6 me-1"></i>medium</span>
                                                <div>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu notes-menu dropdown-menu-end">
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-note-units"><span><i data-feather="edit"></i></span>Edit</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete-modal"><span><i data-feather="trash-2"></i></span>Delete</a>
                                                        <a href="javascript:void(0);" class="dropdown-item"><span><i data-feather="star"></i></span>Not Important</a>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#view-note-units"><span><i data-feather="eye"></i></span>View</a>
                                                    </div>
                                                </div>															
                                            </div>
                                            <div class="my-3">
                                                <h5 class="text-truncate mb-1"><a href="javascript:void(0);">Research a topic interested</a></h5>
                                                <p class="mb-3 d-flex align-items-center text-dark"><i class="ti ti-calendar me-1"></i>28 Jan 2024</p>
                                                <p class="text-truncate line-clamb-2 text-wrap">Research a topic interested by listen actively and attentively.</p>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                                        <img src="./build/img/profiles/avatar-10.jpg" alt="Profile" class="img-fluid rounded-circle">																
                                                    </a>	
                                                    <span class="text-success d-flex align-items-center"><i class="fas fa-square square-rotate fs-10 me-1"></i>Work</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);" class="me-2">
                                                        <span><i class="fas fa-star text-warning"></i></span>
                                                    </a>
                                                    <a href="javascript:void(0);">
                                                        <span><i class="ti ti-trash text-danger"></i></span>
                                                    </a>
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
    <!-- /Page wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
