<?php $page = 'file-manager'; ?>
@extends('layout.mainlayout')
@section('content')
   
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content pb-4">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3 pb-3 border-bottom position-relative">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">File Manager</h3>
                 <nav>
                       <ol class="breadcrumb mb-0">
                          <li class="breadcrumb-item">
                            <a href="javascript:void(0);">Dashboard</a>
                          </li>
                          <li class="breadcrumb-item">
                            Application
                          </li>
                          <li class="breadcrumb-item active" aria-current="page">File Manager</li>
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
            </div>
            <a id="toggle_btn2" class="notes-tog position-absolute start-0 avatar avatar-sm rounded-circle bg-primary text-white" href="javascript:void(0);">
                <i class="fas fa-chevron-left"></i>
            </a>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-12 sidebars-right theiaStickySidebar section-bulk-widget">
                <aside class="border bg-white rounded-3 p-4 mb-3 mt-4">
                    <h5 class="d-flex align-items-center border-bottom mb-3 pb-3"><span class="me-2 d-flex align-items-center0"><i class="ti ti-folder fs-20"></i></span>Files</h5>
                    <a href="javascript:void(0);" class="d-flex align-items-center justify-content-center btn btn-primary mb-3 btn-icon w-100">
                        <i class="ti ti-square-rounded-plus me-2"></i> New
                    </a>
                    <div class="d-flex flex-column active-dark-btn mb-3">
                        <a href="{{url('file-manager')}}" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 active mb-1"><i class="ti ti-file-text me-2"></i>My Files</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-star me-2"></i>Google Drive</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-send me-2"></i>Dropbox</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-file-alert me-2"></i>Shared With Me</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-file-analytics me-2"></i>Document</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-clock me-2"></i>Recent</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-stars me-2"></i>Favourites</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-shield me-2"></i>Archived</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-trash me-2"></i>Deleted</a>
                        <a href="javascript:void(0);" class="rounded p-2 d-flex align-items-center fw-semibold fs-15 mb-1"><i class="ti ti-settings me-2"></i>Settings</a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <h6>Storage</h6>
                        </div>
                        <span>70%</span>
                    </div>
                    <div class="progress progress-sm my-2">
                          <div class="progress-bar bg-danger rounded" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span>78.5 GB of 1 TB Free Used</span>
                </aside>
            </div>

            <div class="col-lg-9 budget-role-notes">
                <div class="row">
                    <div class="col-12">
                        <div class="section-bulk-wrap">
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
                                    <i class="ti ti-filter feather-filter info-img ms-0"></i>
                                    <select class="select">
                                        <option>Recent</option>
                                        <option>Last Modified</option>
                                        <option>Last Modified by me</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- /Overview -->
                <div class="d-block">
                    <h4 class="mb-3">Overview</h4>
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <div class="mb-3">
                                <a href="javascript:void(0);" class="d-flex align-items-center justify-content-center bg-light-orange p-3 rounded-top">
                                    <span class="d-flex align-items-center justify-content-center p-4"><img src="{{URL::asset('build/img/icons/folder.svg')}}" alt="Folder"></span>
                                </a>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                                    <h5><a href="javascript:void(0);">Folders</a></h5>
                                    <span class="text-muted">300 Files</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="mb-3">
                                <a href="javascript:void(0);" class="d-flex align-items-center justify-content-center bg-light-red p-3 rounded-top">
                                    <span class="d-flex align-items-center justify-content-center p-4"><img src="{{URL::asset('build/img/icons/pdf-02.svg')}}" alt="Folder"></span>
                                </a>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                                    <h6><a href="javascript:void(0);">PDF</a></h6>
                                    <span>50 Files</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="mb-3">
                                <a href="javascript:void(0);" class="d-flex align-items-center justify-content-center bg-light-green p-3 rounded-top">
                                    <span class="d-flex align-items-center justify-content-center p-4"><img src="{{URL::asset('build/img/icons/image.svg')}}" alt="Folder"></span>
                                </a>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                                    <h6><a href="javascript:void(0);">Images</a></h6>
                                    <span>240 Files</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="mb-3">
                                <a href="javascript:void(0);" class="d-flex align-items-center justify-content-center bg-light-red p-3 rounded-top">
                                    <span class="d-flex align-items-center justify-content-center p-4"><img src="build/img/icons/video.svg" alt="Folder"></span>
                                </a>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                                    <h6><a href="javascript:void(0);">Videos</a></h6>
                                    <span>30 Files</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="mb-3">
                                <a href="javascript:void(0);" class="d-flex align-items-center justify-content-center bg-light-orange p-3 rounded-top">
                                    <span class="d-flex align-items-center justify-content-center p-4"><img src="{{URL::asset('build/img/icons/audio.svg')}}" alt="Folder"></span>
                                </a>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-bottom">
                                    <h6><a href="javascript:void(0);">Audios</a></h6>
                                    <span>100 Files</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Overview -->

                <!-- Filemanager Items -->
                <div class="d-block">
                      <div class="mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-center mb-3">
                            <h4>Folders</h4>
                            <div class="owl-nav slide-nav6 text-end nav-control ms-3"></div>
                        </div>
                        <div class="owl-carousel folders-carousel owl-theme">
                            <div class="p-3 border p-3 bg-white rounded">
                              <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                  <img src="{{URL::asset('build/img/icons/folder.svg')}}" alt="Folder" class="me-2">
                                  <h5 class="text-nowrap"><a href="javascript:void(0);">Project Details</a></h5>
                                </div>
                                <div class="dropdown">
                                  <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                    class="dropset">
                                    <i class="fa fa-ellipsis-v"></i>
                                  </a>
                                  <ul class="dropdown-menu">
                                    <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                  </ul>
                                </div>
                              </div>
                              <div class="d-flex align-items-center justify-content-start my-3">
                                  <p class="text-primary mb-0 me-2 pe-1">Project plan</p>
                                <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>27 MB</span>
                                <span class="d-flex align-items-center fw-semibold"><i class="ti ti-circle-filled fs-5 me-2"></i>208 Files</span>
                              </div>
                              <div class="d-flex align-items-center justify-content-between">
                                <div class="avatar-list-stacked avatar-group-sm">
                                    <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 1" data-bs-original-title="Member 1"><img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                    <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 2" data-bs-original-title="Member 2"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                    <span class="fw-semibold mt-2">
                                       <a class="text-success ms-3" href="javascript:void(0);">2 Members</a>
                                    </span>
                                </div>
                                <a  href="javascript:void(0);"><i class="ti ti-star fs-16"></i></a>
                              </div>
                            </div>
                            <div class="p-3 border p-3 bg-white rounded">
                                <div class="d-flex align-items-center justify-content-between">
                                  <div class="d-flex align-items-center">
                                    <img src="{{URL::asset('build/img/icons/folder.svg')}}" alt="Folder" class="me-2">
                                    <h5 class="text-nowrap"><a href="javascript:void(0);">Website Backup</a></h5>
                                  </div>
                                  <div class="dropdown">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                      class="dropset">
                                      <i class="fa fa-ellipsis-v"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                      <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                      <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                    </ul>
                                  </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-start my-3">
                                    <p class="text-primary mb-0 me-2 pe-1">Important</p>
                                  <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>600 MB</span>
                                  <span class="d-flex align-items-center fw-semibold"><i class="ti ti-circle-filled fs-5 me-2"></i>48 Files</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                  <div class="avatar-list-stacked avatar-group-sm">
                                      <span class="avatar border-0">
                                          <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 1" data-bs-original-title="Member 1"><img src="{{URL::asset('build/img/profiles/avatar-11.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                      </span>
                                      <span class="avatar border-0">
                                          <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 2" data-bs-original-title="Member 2"><img src="{{URL::asset('build/img/profiles/avatar-12.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                      </span>
                                      <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 2" data-bs-original-title="Member 3"><img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                    <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 2" data-bs-original-title="Member 3"><img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                      <span class="fw-semibold mt-2">
                                         <a class="text-success ms-3" href="javascript:void(0);">4 Members</a>
                                      </span>
                                  </div>
                                  <a  href="javascript:void(0);"><i class="ti ti-star fs-16"></i></a>
                                </div>
                            </div>
                            <div class="p-3 border p-3 bg-white rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                <img src="{{URL::asset('build/img/icons/folder.svg')}}" alt="Folder" class="me-2">
                                <h5 class="text-nowrap"><a href="javascript:void(0);">Number_Proj Photos</a></h5>
                                </div>
                                <div class="dropdown">
                                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                    class="dropset">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                </ul>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-start my-3">
                                <p class="text-primary mb-0 me-2 pe-1">Project plan</p>
                                <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>150 MB</span>
                                <span class="d-flex align-items-center fw-semibold"><i class="ti ti-circle-filled fs-5 me-2"></i>208 Files</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="avatar-list-stacked avatar-group-sm">
                                    <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 1" data-bs-original-title="Member 1"><img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                    <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 2" data-bs-original-title="Member 2"><img src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                    <span class="avatar border-0">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Member 2" data-bs-original-title="Member 3"><img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" class="rounded-circle" alt="Avatar"></a>
                                    </span>
                                    <span class="fw-semibold mt-2">
                                        <a class="text-success ms-3" href="javascript:void(0);">+ 3 Members</a>
                                    </span>
                                </div>
                                <a  href="javascript:void(0);"><i class="ti ti-star fs-16"></i></a>
                            </div>
                            </div>
                        </div>
                    </div>
                      <div class="mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-center mb-3">
                            <h4>Files</h4>
                            <div class="owl-nav slide-nav7 text-end nav-control ms-3"></div>
                        </div>
                        <div class="owl-carousel files-carousel owl-theme">
                            <div class="border rounded-3 bg-white p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <img src="{{URL::asset('build/img/icons/pdf-02.svg')}}" alt="Folder" class="me-2">
                                        <h5 class="text-nowrap"><a href="javascript:void(0);">hsa.pdf</a></h5>
                                      </div>
                                    <div class="d-flex align-items-center">
                                        <a  href="javascript:void(0);"><i class="fa fa-star me-2"></i></a>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                                class="dropset">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-start mt-3">
                                    <p class="text-primary mb-0 me-2">Last edited  14 Jul</p>
                                    <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>150 MB</span>
                                </div>
                            </div>
                            <div class="border rounded-3 bg-white p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <img src="{{URL::asset('build/img/icons/pdf-02.svg')}}" alt="Folder" class="me-2">
                                        <h5 class="text-nowrap"><a href="javascript:void(0);">Haird.pdf</a></h5>
                                      </div>
                                    <div class="d-flex align-items-center">
                                        <a  href="javascript:void(0);"><i class="fa fa-star me-2"></i></a>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                                class="dropset">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-start mt-3">
                                    <p class="text-primary mb-0 me-2">Last edited  15 Jul</p>
                                    <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>200 MB</span>
                                </div>
                            </div>
                            <div class="border rounded-3 bg-white p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <img src="{{URL::asset('build/img/icons/xls.svg')}}" alt="Folder" class="me-2">
                                        <h5 class="text-nowrap"><a href="javascript:void(0);">Estimation.xls</a></h5>
                                      </div>
                                    <div class="d-flex align-items-center">
                                        <a  href="javascript:void(0);"><i class="fa fa-star me-2"></i></a>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                                class="dropset">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-start mt-3">
                                    <p class="text-primary mb-0 me-2">Last edited  16 Jul</p>
                                    <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>500 KB</span>
                                </div>
                            </div>
                            <div class="border rounded-3 bg-white p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <img src="{{URL::asset('build/img/icons/pdf-02.svg')}}" alt="Folder" class="me-2">
                                        <h5 class="text-nowrap"><a href="javascript:void(0);">Haird.pdf</a></h5>
                                      </div>
                                    <div class="d-flex align-items-center">
                                        <a  href="javascript:void(0);"><i class="fa fa-star me-2"></i></a>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                                class="dropset">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-start mt-3">
                                    <p class="text-primary mb-0 me-2">Last edited  15 Jul</p>
                                    <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>200 MB</span>
                                </div>
                            </div>
                            <div class="border rounded-3 bg-white p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <img src="{{URL::asset('build/img/icons/xls.svg')}}" alt="Folder" class="me-2">
                                        <h5 class="text-nowrap"><a href="javascript:void(0);">Estimation.xls</a></h5>
                                      </div>
                                    <div class="d-flex align-items-center">
                                        <a  href="javascript:void(0);"><i class="fa fa-star me-2"></i></a>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"
                                                class="dropset">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-start mt-3">
                                    <p class="text-primary mb-0 me-2">Last edited  16 Jul</p>
                                    <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>500 KB</span>
                                </div>
                            </div>
                        </div>
                      </div>
                      <div class="mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-center mb-3">
                            <h4>Videos</h4>
                            <div class="owl-nav slide-nav8 text-end nav-control ms-3"></div>
                        </div>
                        <div class="owl-carousel video-section">
                            <div class="item">
                                  <div>
                                    <video width="100" height="100" class="js-player" crossorigin playsinline poster="build/img/file-manager/video1.jpg">
                                        <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4" type="video/mp4">
                                    </video>
                                </div>

                                <div class="bg-white p-3 rounded-bottom">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6><a href="javascript:void(0);">Demo_dw</a></h6>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="d-flex align-items-center"><i data-feather="star" class="feather-16 me-2"></i></a>
                                            <div class="dropdown">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-start mt-3">
                                        <p class="text-primary mb-0 me-2">Last edited  14 Jul</p>
                                        <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>150 MB</span>
                                    </div>
                                </div>

                              </div>
                            <div class="item">
                                <div>
                                    <video class="js-player" crossorigin playsinline poster="build/img/file-manager/video2.jpg">
                                        <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4" type="video/mp4">
                                    </video>
                                </div>
                                <div class="bg-white p-3 rounded-bottom">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6><a href="javascript:void(0);">Android_bike.mp4</a></h6>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="d-flex align-items-center"><i data-feather="star" class="feather-16 me-2"></i></a>
                                            <div class="dropdown">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-start mt-3">
                                        <p class="text-primary mb-0 me-2">Last edited  15 Jul</p>
                                        <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>50 MB</span>
                                    </div>
                                </div>
                              </div>
                            <div class="item">
                              <div>
                                      <video class="js-player" crossorigin playsinline poster="build/img/file-manager/video3.jpg">
                                        <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4" type="video/mp4">
                                    </video>
                              </div>
                              <div class="bg-white rounded-bottom p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6><a href="javascript:void(0);">Demoparticles.mp4</a></h6>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="d-flex align-items-center"><i data-feather="star" class="feather-16 me-2"></i></a>
                                            <div class="dropdown">
                                                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Share</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Copy</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Move</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Download</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Rename</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Archeived</a></li>
                                                    <li><a href="javascript:void(0);" class="dropdown-item">Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-start mt-3">
                                        <p class="text-primary mb-0 me-2">Last edited  16 Jul</p>
                                        <span class="d-flex align-items-center fw-semibold me-2"><i class="ti ti-circle-filled fs-5 me-2"></i>250 MB</span>
                                    </div>
                                </div>
                              </div>
                        </div>
                      </div>
                </div>
                <!-- /Filemanager Items -->
                
                <!-- /Filemanager List --> 
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                        <h4 class="mb-3">All Files</h4>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="input-icon-start mb-3 me-2 position-relative">
                                <span class="icon-addon">
                                    <i class="ti ti-calendar"></i>
                                </span>
                                <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                                    value="Academic Year : 2024 / 2025">
                            </div>
                            <div class="dropdown mb-3">
                                <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                                    data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
                                </a>
                                <ul class="dropdown-menu p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Ascending
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Descending
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Recently Viewed
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Recently Added
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0 py-3">

                        <!-- Student List -->
                        <div class="custom-datatable-filter table-responsive">
                            <table class="table datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="no-sort">
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox" id="select-all">
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Last Modified</th>
                                        <th>Size</th>
                                        <th>Owned Memebr</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/icons/folder.svg')}}" class="img-fluid" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Digimed</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>22 Apr 2024</td>
                                        <td>200 MB</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Janet</a>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#"
                                                    class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-trash me-2"></i>Permanent Delete</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-edit-circle me-2"></i>Restore File</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/icons/xls.svg')}}" class="img-fluid" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Estimation</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>23 Apr 2024</td>
                                        <td>100 MB</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Henriques</a>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#"
                                                    class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-trash me-2"></i>Permanent Delete</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-edit-circle me-2"></i>Restore File</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/icons/pdf-02.svg')}}" class="img-fluid" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Intro.pdf</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>24 Apr 2024</td>
                                        <td>150 MB</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Nolan Harris</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#"
                                                    class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-trash me-2"></i>Permanent Delete</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-edit-circle me-2"></i>Restore File</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/icons/video.svg')}}" class="img-fluid" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Demoworrking.mp4</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>25 Apr 2024</td>
                                        <td>250 MB</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">Sarah</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#"
                                                    class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-trash me-2"></i>Permanent Delete</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-edit-circle me-2"></i>Restore File</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/icons/audio.svg')}}" class="img-fluid" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">voice.mp3</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>26 Apr 2024</td>
                                        <td>180 MB</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="#" class="avatar avatar-md"><img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a href="#">William</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#"
                                                    class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical fs-14"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-trash me-2"></i>Permanent Delete</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item rounded-1" href="#"><i class="ti ti-edit-circle me-2"></i>Restore File</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- /Student List -->
                    </div>
                </div>
                <!-- /Filemanager List -->

            </div>
        </div>

    </div>
    <!-- /Content -->
        
</div>
<!-- /Page Wrapper -->
   
@endsection
