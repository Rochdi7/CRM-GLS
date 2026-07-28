<?php $page = 'blog'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content content-two">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Blogs</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Pages</li>
                        <li class="breadcrumb-item active" aria-current="page">Blogs</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="pe-1 mb-2">
                    <a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip"
                        data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                        <i class="ti ti-refresh"></i>
                    </a>
                </div>
                <div class="pe-1 mb-2">
                    <button type="button" class="btn btn-outline-light bg-white btn-icon me-1"
                        data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print"
                        data-bs-original-title="Print">
                        <i class="ti ti-printer"></i>
                    </button>
                </div>
                <div class="dropdown me-2 mb-2">
                    <a href="javascript:void(0);"
                        class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"
                        data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-2"></i>Export
                    </a>
                    <ul class="dropdown-menu  dropdown-menu-end p-3">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                    class="ti ti-file-type-pdf me-1"></i>Export as PDF</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                    class="ti ti-file-type-xls me-1"></i>Export as Excel </a>
                        </li>
                    </ul>
                </div>
                <div class="mb-2">
                    <a href="#" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#add_blog"><i class="ti ti-square-rounded-plus me-2"></i>Add Blog</a>
                </div>
            </div>
        </div>

        <!-- Table Filter -->
        <div class="d-flex justify-content-between flex-wrap">
            <div class="dropdown mb-3 me-2">
                <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                        class="ti ti-filter me-2"></i>Filter</a>
                <div class="dropdown-menu drop-width">
                    <form action="{{url('blog')}}">
                        <div class="d-flex align-items-center border-bottom p-3">
                            <h4>Filter</h4>
                        </div>
                        <div class="p-3 border-bottom">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>Education</option>
                                            <option>Clear Goals</option>
                                            <option>Advice</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Author</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>Jordan</option>
                                            <option>Rubiaana</option>
                                            <option>Daniel</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>Active</option>
                                            <option>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-0">
                                        <label class="form-label">Added Date</label>
                                        <input type="date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 d-flex align-items-center justify-content-end">
                            <a href="#" class="btn btn-light me-3">Reset</a>
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="dropdown mb-3">
                <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
                <ul class="dropdown-menu p-3">
                    <li>
                        <a href="javascript:void(0);" class="dropdown-item rounded-1 active">
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
        <!-- /Table Filter -->

        <div class="row">
            <div class="col-md-12">

                <!-- Blog List -->
                <div class="card mb-3">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="blog-content d-flex align-items-center mb-3 me-3 w-40">
                                <a href="javascript:void(0);"
                                    class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/blogs/blog-01.jpg')}}" alt="img">
                                </a>
                                <div class="overflow-hidden">
                                    <ul class="d-flex align-items-center flex-wrap row-gap-2">
                                        <li class="pe-2 me-2 border-end"><i
                                                class="ti ti-calendar me-1"></i>July 2024</li>
                                        <li class="d-flex align-items-center"><span
                                                class="avatar avatar-xs me-1"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}"
                                                    alt="img"></span>Jordan</li>
                                    </ul>
                                    <h5 class="text-truncate my-2"><a href="javascript:void(0);">Personal
                                            development and self-improvement</a></h5>
                                    <span class="badge badge-soft-primary fs-12">Education</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>3000</h6>
                                    <p class="fs-12">Likes</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>454</h6>
                                    <p class="fs-12">Comments</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>102</h6>
                                    <p class="fs-12">Share</p>
                                </div>
                                <div class="p-0 mb-3 text-center">
                                    <h6>350</h6>
                                    <p class="fs-12">Reviews</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-success d-inline-flex align-items-center me-2"><i
                                        class="ti ti-circle-filled fs-6 me-1"></i>Active</span>
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_blog"><i
                                                    class="ti ti-edit-circle me-2"></i>Edit</a>
                                        </li>


                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"><i
                                                    class="ti ti-trash-x me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Blog List -->

                <!-- Blog List -->
                <div class="card mb-3">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="blog-content d-flex align-items-center mb-3 me-3 w-40">
                                <a href="javascript:void(0);"
                                    class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/blogs/blog-02.jpg')}}" alt="img">
                                </a>
                                <div class="overflow-hidden">
                                    <ul class="d-flex align-items-center flex-wrap row-gap-2">
                                        <li class="pe-2 me-2 border-end"><i class="ti ti-calendar me-1"></i>12
                                            July 2024</li>
                                        <li class="d-flex align-items-center"><span
                                                class="avatar avatar-xs me-1"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-24.jpg')}}"
                                                    alt="img"></span>Rubiaana</li>
                                    </ul>
                                    <h5 class="text-truncate my-2"><a href="javascript:void(0);">5 Effective
                                            Strategies to Boost Your Productivity</a></h5>
                                    <span class="badge badge-soft-primary fs-12">Clear Goals</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>1200</h6>
                                    <p class="fs-12">Likes</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>12</h6>
                                    <p class="fs-12">Comments</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>89</h6>
                                    <p class="fs-12">Share</p>
                                </div>
                                <div class="p-0 mb-3 text-center">
                                    <h6>56</h6>
                                    <p class="fs-12">Reviews</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-success d-inline-flex align-items-center me-2"><i
                                        class="ti ti-circle-filled fs-6 me-1"></i>Active</span>
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_blog"><i
                                                    class="ti ti-edit-circle me-2"></i>Edit</a>
                                        </li>


                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"><i
                                                    class="ti ti-trash-x me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Blog List -->

                <!-- Blog List -->
                <div class="card mb-3">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="blog-content d-flex align-items-center mb-3 me-3 w-40">
                                <a href="javascript:void(0);"
                                    class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/blogs/blog-03.jpg')}}" alt="img">
                                </a>
                                <div class="overflow-hidden">
                                    <ul class="d-flex align-items-center flex-wrap row-gap-2">
                                        <li class="pe-2 me-2 border-end"><i class="ti ti-calendar me-1"></i>10
                                            July 2024</li>
                                        <li class="d-flex align-items-center"><span
                                                class="avatar avatar-xs me-1"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}"
                                                    alt="img"></span>Daniel</li>
                                    </ul>
                                    <h5 class="text-truncate my-2"><a href="javascript:void(0);">Prioritize Your
                                            Tasks</a></h5>
                                    <span class="badge badge-soft-primary fs-12">Education</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>5000</h6>
                                    <p class="fs-12">Likes</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>535</h6>
                                    <p class="fs-12">Comments</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>24</h6>
                                    <p class="fs-12">Share</p>
                                </div>
                                <div class="p-0 mb-3 text-center">
                                    <h6>78</h6>
                                    <p class="fs-12">Reviews</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-danger d-inline-flex align-items-center me-2"><i
                                        class="ti ti-circle-filled fs-6 me-1"></i>Inactive</span>
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_blog"><i
                                                    class="ti ti-edit-circle me-2"></i>Edit</a>
                                        </li>


                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"><i
                                                    class="ti ti-trash-x me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Blog List -->

                <!-- Blog List -->
                <div class="card mb-3">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="blog-content d-flex align-items-center mb-3 me-3 w-40">
                                <a href="javascript:void(0);"
                                    class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/blogs/blog-04.jpg')}}" alt="img">
                                </a>
                                <div class="overflow-hidden">
                                    <ul class="d-flex align-items-center flex-wrap row-gap-2">
                                        <li class="pe-2 me-2 border-end"><i class="ti ti-calendar me-1"></i>10
                                            July 2024</li>
                                        <li class="d-flex align-items-center"><span
                                                class="avatar avatar-xs me-1"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}"
                                                    alt="img"></span>Daniel</li>
                                    </ul>
                                    <h5 class="text-truncate my-2"><a href="javascript:void(0);">How to
                                            eliminate Eliminate Distractions</a></h5>
                                    <span class="badge badge-soft-primary fs-12">Advice</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>2345</h6>
                                    <p class="fs-12">Likes</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>135</h6>
                                    <p class="fs-12">Comments</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>32</h6>
                                    <p class="fs-12">Share</p>
                                </div>
                                <div class="p-0 mb-3 text-center">
                                    <h6>154</h6>
                                    <p class="fs-12">Reviews</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-danger d-inline-flex align-items-center me-2"><i
                                        class="ti ti-circle-filled fs-6 me-1"></i>Inactive</span>
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_blog"><i
                                                    class="ti ti-edit-circle me-2"></i>Edit</a>
                                        </li>


                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"><i
                                                    class="ti ti-trash-x me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Blog List -->

                <!-- Blog List -->
                <div class="card mb-3">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="blog-content d-flex align-items-center mb-3 me-3 w-40">
                                <a href="javascript:void(0);"
                                    class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/blogs/blog-05.jpg')}}" alt="img">
                                </a>
                                <div class="overflow-hidden">
                                    <ul class="d-flex align-items-center flex-wrap row-gap-2">
                                        <li class="pe-2 me-2 border-end"><i class="ti ti-calendar me-1"></i>18
                                            July 2024</li>
                                        <li class="d-flex align-items-center"><span
                                                class="avatar avatar-xs me-1"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}"
                                                    alt="img"></span>Henrita</li>
                                    </ul>
                                    <h5 class="text-truncate my-2"><a href="javascript:void(0);">Steps to Take
                                            Regular Breaks before learning</a></h5>
                                    <span class="badge badge-soft-primary fs-12">Advice</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>2454</h6>
                                    <p class="fs-12">Likes</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>165</h6>
                                    <p class="fs-12">Comments</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>22</h6>
                                    <p class="fs-12">Share</p>
                                </div>
                                <div class="p-0 mb-3 text-center">
                                    <h6>164</h6>
                                    <p class="fs-12">Reviews</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-success d-inline-flex align-items-center me-2"><i
                                        class="ti ti-circle-filled fs-6 me-1"></i>Active</span>
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_blog"><i
                                                    class="ti ti-edit-circle me-2"></i>Edit</a>
                                        </li>


                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"><i
                                                    class="ti ti-trash-x me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Blog List -->

                <!-- Blog List -->
                <div class="card mb-3">
                    <div class="card-body p-3 pb-0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="blog-content d-flex align-items-center mb-3 me-3 w-40">
                                <a href="javascript:void(0);"
                                    class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/blogs/blog-06.jpg')}}" alt="img">
                                </a>
                                <div class="overflow-hidden">
                                    <ul class="d-flex align-items-center flex-wrap row-gap-2">
                                        <li class="pe-2 me-2 border-end"><i class="ti ti-calendar me-1"></i>18
                                            July 2024</li>
                                        <li class="d-flex align-items-center"><span
                                                class="avatar avatar-xs me-1"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}"
                                                    alt="img"></span>Zlatan</li>
                                    </ul>
                                    <h5 class="text-truncate my-2"><a href="javascript:void(0);">Using Time
                                            Management Techniques</a></h5>
                                    <span class="badge badge-soft-primary fs-12">Clear Goals</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>4343</h6>
                                    <p class="fs-12">Likes</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>233</h6>
                                    <p class="fs-12">Comments</p>
                                </div>
                                <div class="pe-4 me-4 mb-3 border-end text-center">
                                    <h6>11</h6>
                                    <p class="fs-12">Share</p>
                                </div>
                                <div class="p-0 mb-3 text-center">
                                    <h6>454</h6>
                                    <p class="fs-12">Reviews</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge badge-soft-success d-inline-flex align-items-center me-2"><i
                                        class="ti ti-circle-filled fs-6 me-1"></i>Active</span>
                                <div class="dropdown">
                                    <a href="#"
                                        class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_blog"><i
                                                    class="ti ti-edit-circle me-2"></i>Edit</a>
                                        </li>


                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete-modal"><i
                                                    class="ti ti-trash-x me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Blog List -->

                <div class="text-center">
                    <a href="#" class="btn btn-primary d-inline-flex align-items-center"><i class="ti ti-loader-3 me-2"></i>Load More</a>
                </div>

            </div>
        </div>

    </div>
</div>
<!-- /Page Wrapper -->	

@component('components.modal-popup')        
@endcomponent
@endsection