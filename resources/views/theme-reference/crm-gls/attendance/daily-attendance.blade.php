<?php $page = 'daily-attendance'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <div class="content">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Daily Attendance</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="javascript:void(0);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="javascript:void(0);">Report</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Daily Attendance</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="pe-1 mb-2">
                    <a href="#" class="btn btn-outline-light bg-white btn-icon me-1"
                        data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh"
                        data-bs-original-title="Refresh">
                        <i class="ti ti-refresh"></i>
                    </a>
                </div>
                <div class="pe-1 mb-2">
                    <button type="button"
                        class="btn btn-outline-light bg-white btn-icon me-1"
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
            </div>
        </div>
        <!-- /Page Header -->
        
        <!-- Filter Section -->
        <div class="filter-wrapper">
            <!-- List Tab -->
            <div class="list-tab">
                <ul>
                    <li>
                        <a href="{{url('attendance-report')}}">Attendance Report</a>
                    </li>
                    <li>
                        <a href="{{url('student-attendance-type')}}">Students Attendance Type</a>
                    </li>
                    <li>
                        <a href="{{url('daily-attendance')}}" class="active">Daily Attendance</a>
                    </li>
                    <li>
                        <a href="{{url('student-day-wise')}}">Student Day Wise</a>
                    </li>
                    <li>
                        <a href="{{url('teacher-day-wise')}}">Teacher Day Wise</a>
                    </li>
                    <li>
                        <a href="{{url('teacher-report')}}">Teacher Report</a>
                    </li>
                    <li>
                        <a href="{{url('staff-day-wise')}}">Staff Day Wise</a>
                    </li>
                    <li>
                        <a href="{{url('staff-report')}}">Staff Report</a>
                    </li>
                </ul>
            </div>
            <!-- /List Tab -->
        </div>
        <!-- /Filter Section -->

        <!-- Attendance List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Daily Attendance</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control  date-range bookingrange"
                            placeholder="Select" value="Academic Year : 2024 / 2025">
                    </div>
                    <div class="dropdown mb-3 me-2">
                        <a href="javascript:void(0);"
                            class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Filter</a>
                        <div class="dropdown-menu drop-width">
                            <form action="{{url('daily-attendance')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Attendance Date</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>15 May 2024</option>
                                                    <option>16 May 2024</option>
                                                    <option>17 May 2024</option>
                                                </select>
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
                        <a href="javascript:void(0);"
                            class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown"><i
                                class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
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
                                <th>Class</th>
                                <th>Section</th>
                                <th>Total Present</th>
                                <th>Total Absent</th>
                                <th>Present %</th>
                                <th>Absent %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>III</td>
                                <td>A</td>
                                <td>69</td>
                                <td>2</td>
                                <td>98%</td>
                                <td>2%</td>
                            </tr>
                            <tr>
                                <td>IV</td>
                                <td>A</td>
                                <td>45</td>
                                <td>7</td>
                                <td>78%</td>
                                <td>22%</td>
                            </tr>
                            <tr>
                                <td>II</td>
                                <td>B</td>
                                <td>69</td>
                                <td>8</td>
                                <td>89%</td>
                                <td>11%</td>
                            </tr>
                            <tr>
                                <td>I</td>
                                <td>C</td>
                                <td>54</td>
                                <td>7</td>
                                <td>98%</td>
                                <td>1%</td>
                            </tr>
                            <tr>
                                <td>II</td>
                                <td>A</td>
                                <td>65</td>
                                <td>1</td>
                                <td>98%</td>
                                <td>2%</td>
                            </tr>
                            <tr>
                                <td>III</td>
                                <td>B</td>
                                <td>78</td>
                                <td>2</td>
                                <td>72%</td>
                                <td>28%</td>
                            </tr>
                            <tr>
                                <td>V</td>
                                <td>C</td>
                                <td>65</td>
                                <td>0</td>
                                <td>100%</td>
                                <td>0%</td>
                            </tr>
                            <tr>
                                <td>VI</td>
                                <td>A</td>
                                <td>45</td>
                                <td>2</td>
                                <td>98%</td>
                                <td>1%</td>
                            </tr>
                            <tr>
                                <td>VIII</td>
                                <td>B</td>
                                <td>47</td>
                                <td>2</td>
                                <td>98%</td>
                                <td>2%</td>
                            </tr>
                            <tr>
                                <td>VII</td>
                                <td>C</td>
                                <td>45</td>
                                <td>7</td>
                                <td>89%</td>
                                <td>11%</td>
                            </tr>
                            <tr>
                                <td>IX</td>
                                <td>A</td>
                                <td>45</td>
                                <td>1</td>
                                <td>98%</td>
                                <td>2%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /Student List -->
            </div>
        </div>
        <!-- /Attendance List -->

    </div>
</div>
<!-- /Page Wrapper --> 
@endsection
