<?php $page = 'grade-report'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Grade Report
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        Report
        @endslot
        @slot('item3')
        Grade Report
        @endslot
       @endcomponent
        

        <!-- Student List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Grade Report List</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                            value="Academic Year : 2024 / 2025">
                    </div>
                    <div class="dropdown mb-3 me-2">
                        <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Filter</a>
                        <div class="dropdown-menu drop-width">
                            <form action="{{url('grade-report')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Class</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>I</option>
                                                    <option>II</option>
                                                    <option>III</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">Section</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>A</option>
                                                    <option>B</option>
                                                    <option>C</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Exam Type</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Week Test</option>
                                                    <option>Monthly Test</option>
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
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>English</th>
                                <th>Spanish</th>
                                <th>Physics</th>
                                <th>Chemistry</th>
                                <th>Maths</th>
                                <th>Computer</th>
                                <th>Env Science</th>
                                <th>Total</th>
                                <th>Percent(%)</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892434</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-01.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35013</span>
                                        </div>
                                    </div>
                                </td>
                                <td>95</td>
                                <td>93</td>
                                <td>88</td>
                                <td>
                                    90
                                </td>
                                <td>85</td>
                                <td>98</td>
                                <td>
                                    95
                                </td>
                                <td>644</td>
                                <td>92</td>
                                <td>O</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892433</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-02.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35012</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-danger">30</td>
                                <td>35</td>
                                <td>36</td>
                                <td class="text-danger">
                                    28
                                </td>
                                <td>38</td>
                                <td>40</td>
                                <td>
                                    37
                                </td>
                                <td>244</td>
                                <td>34</td>
                                <td>F</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892432</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-03.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a
                                                    href="{{url('student-details')}}">Kathleen</a></p>
                                            <span class="fs-12">Roll No : 35011</span>
                                        </div>
                                    </div>
                                </td>
                                <td>70</td>
                                <td>80</td>
                                <td>85</td>
                                <td>
                                    78
                                </td>
                                <td>82</td>
                                <td>83</td>
                                <td>
                                    80
                                </td>
                                <td>558</td>
                                <td>79</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892431</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-04.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35010</span>
                                        </div>
                                    </div>
                                </td>
                                <td>60</td>
                                <td>68</td>
                                <td>65</td>
                                <td>
                                    70
                                </td>
                                <td>67</td>
                                <td>72</td>
                                <td>
                                    75
                                </td>
                                <td>477</td>
                                <td>68</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892430</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-05.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Lisa</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35009</span>
                                        </div>
                                    </div>
                                </td>
                                <td>36</td>
                                <td class="text-danger">30</td>
                                <td>40</td>
                                <td>
                                    38
                                </td>
                                <td>50</td>
                                <td>48</td>
                                <td>
                                    43
                                </td>
                                <td>285</td>
                                <td>40</td>
                                <td>F</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892429</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35008</span>
                                        </div>
                                    </div>
                                </td>
                                <td>43</td>
                                <td>50</td>
                                <td>62</td>
                                <td>
                                    70
                                </td>
                                <td>65</td>
                                <td>58</td>
                                <td>
                                    60
                                </td>
                                <td>408</td>
                                <td>58</td>
                                <td>B</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892428</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-07.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35007</span>
                                        </div>
                                    </div>
                                </td>
                                <td>72</td>
                                <td>80</td>
                                <td>75</td>
                                <td>
                                    78
                                </td>
                                <td>84</td>
                                <td>87</td>
                                <td>
                                    76
                                </td>
                                <td>552</td>
                                <td>78</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892427</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-08.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Ryan</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35006</span>
                                        </div>
                                    </div>
                                </td>
                                <td>40</td>
                                <td>48</td>
                                <td>42</td>
                                <td>
                                    47
                                </td>
                                <td class="text-danger">32</td>
                                <td>58</td>
                                <td>
                                    50
                                </td>
                                <td>317</td>
                                <td>45</td>
                                <td>F</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892426</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-09.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35004</span>
                                        </div>
                                    </div>
                                </td>
                                <td>60</td>
                                <td>62</td>
                                <td>65</td>
                                <td>
                                    70
                                </td>
                                <td>72</td>
                                <td>63</td>
                                <td>
                                    78
                                </td>
                                <td>470</td>
                                <td>67</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td><a href="#" class="link-primary">AD9892425</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-10.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="{{url('student-details')}}">Richard</a>
                                            </p>
                                            <span class="fs-12">Roll No : 35003</span>
                                        </div>
                                    </div>
                                </td>
                                <td>72</td>
                                <td>78</td>
                                <td>85</td>
                                <td>
                                    83
                                </td>
                                <td>86</td>
                                <td>90</td>
                                <td>
                                    92
                                </td>
                                <td>586</td>
                                <td>83</td>
                                <td>A+</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /Student List -->
            </div>
        </div>
        <!-- /Student List -->

    </div>
</div>
<!-- /Page Wrapper -->
@endsection