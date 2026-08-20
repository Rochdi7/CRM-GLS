<?php $page = 'routine-teachers'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				<div class="row">

					<!-- Page Header -->
					<div class="col-md-12">
						<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
							<div class="my-auto mb-2">
								<h3 class="page-title mb-1">Teacher Details</h3>
								<nav>
									<ol class="breadcrumb mb-0">
										<li class="breadcrumb-item">
											<a href="{{url('index')}}">Dashboard</a>
										</li>
										<li class="breadcrumb-item">
											<a href="{{url('teachers')}}">Teachers</a>
										</li>
										<li class="breadcrumb-item active" aria-current="page">Teacher Details</li>
									</ol>
								</nav>
							</div>
							<div class="d-flex my-xl-auto right-content align-items-center  flex-wrap">
								<a href="#" class="btn btn-light me-2 mb-2" data-bs-toggle="modal"
									data-bs-target="#login_detail"><i class="ti ti-lock me-2"></i>Login Details</a>
								<a href="{{url('edit-teacher')}}" class="btn btn-primary d-flex align-items-center mb-2"><i
										class="ti ti-edit-circle me-2"></i>Edit Teacher</a>
							</div>
						</div>
					</div>
					<!-- /Page Header -->
				</div>

				<div class="row">

					<!-- Teacher Information -->
					<div class="col-xxl-3 col-lg-4 theiaStickySidebar">
						<div class="card border-white">
							<div class="card-header">
								<div class="d-flex align-items-center flex-wrap row-gap-3">
									<div
										class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
										<img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="img-fluid" alt="img">
									</div>
									<div>
										<h5 class="mb-1">Teresa</h5>
										<p class="text-primary mb-1">T849126</p>
										<p>Joined : 25 May 24</p>
									</div>
								</div>
							</div>
							<div class="card-body">
								<h5 class="mb-3">Basic Information</h5>
								<dl class="row mb-0">
									<dt class="col-6 fw-normal mb-3">Class & Section</dt>
									<dd class="col-6 text-dark mb-3">III, A</dd>
									<dt class="col-6 fw-normal mb-3">Subject</dt>
									<dd class="col-6 text-dark mb-3">Physics</dd>
									<dt class="col-6 fw-normal mb-3">Gender</dt>
									<dd class="col-6 text-dark mb-3">Female</dd>
									<dt class="col-6 fw-normal mb-3">Blood Group</dt>
									<dd class="col-6 text-dark mb-3">O +ve</dd>
									<dt class="col-6 fw-normal mb-3">House</dt>
									<dd class="col-6 text-dark mb-3">Red</dd>
									<dt class="col-6 fw-normal mb-3">Language Known</dt>
									<dd class="col-6 text-dark mb-3">English</dd>
									<dt class="col-6 fw-normal mb-0">Language</dt>
									<dd class="col-6 text-dark mb-0"><span
											class="badge badge-light text-dark me-2">English</span><span
											class="badge badge-light text-dark">Spanish</span></dd>
								</dl>
							</div>
						</div>
						<div class="card border-white">
							<div class="card-body">
								<h5 class="mb-3">Primary Contact Info</h5>
								<div class="d-flex align-items-center mb-3">
									<span
										class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
											class="ti ti-phone"></i></span>
									<div>
										<span class="fs-12 mb-1">Phone Number</span>
										<p class="text-dark">+1 46548 84498</p>
									</div>
								</div>
								<div class="d-flex align-items-center">
									<span
										class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
											class="ti ti-mail"></i></span>
									<div>
										<span class="fs-12 mb-1">Email Address</span>
										<p class="text-dark">jan@example.com</p>
									</div>
								</div>
							</div>
						</div>
						<div class="card border-white">
							<div class="card-body pb-1">
								<h5 class="mb-3">PAN Number / ID Number</h5>
								<div class="d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center mb-3">
										<span
											class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
												class="ti ti-id"></i></span>
										<div>
											<p class="text-dark">343445954908</p>
										</div>
									</div>
									<a class="btn btn-primary btn-icon btn-sm mb-3">
										<i class="ti ti-copy"></i>
									</a>
								</div>
							</div>
						</div>
						<div class="card border-white">
							<div class="card-body pb-1">
								<ul class="nav nav-tabs nav-tabs-bottom mb-3">
									<li class="nav-item"><a class="nav-link active" href="#hostel"
											data-bs-toggle="tab">Hostel</a></li>
									<li class="nav-item"><a class="nav-link" href="#transport"
											data-bs-toggle="tab">Transportation</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade show active" id="hostel">
										<div class="d-flex align-items-center mb-3">
											<span
												class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
													class="ti ti-building-fortress fs-16"></i></span>
											<div>
												<h6 class="mb-1">HI-Hostel, Floor</h6>
												<p class="text-primary">Room No : 25</p>
											</div>
										</div>
									</div>
									<div class="tab-pane fade" id="transport">
										<div class="d-flex align-items-center mb-3">
											<span
												class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
													class="ti ti-bus fs-16"></i></span>
											<div>
												<span class="fs-12 mb-1">Route</span>
												<p class="text-dark">Newyork</p>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-6">
												<div class="mb-3">
													<span class="fs-12 mb-1">Bus Number</span>
													<p class="text-dark">AM 54548</p>
												</div>
											</div>
											<div class="col-sm-6">
												<div class="mb-3">
													<span class="fs-12 mb-1">Pickup Point</span>
													<p class="text-dark">Cincinatti</p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Teacher Information -->

					<div class="col-xxl-9 col-xl-8">
						<div class="row">
							<div class="col-md-12">

								<!-- List -->
								<ul class="nav nav-tabs nav-tabs-bottom mb-4">
									<li>
										<a href="{{url('teacher-details')}}" class="nav-link "><i
												class="ti ti-school me-2"></i>Teacher Details</a>
									</li>
									<li>
										<a href="{{url('routine-teachers')}}" class="nav-link active"><i
												class="ti ti-table-options me-2"></i>Routine</a>
									</li>
									<li>
										<a href="{{url('teacher-leaves')}}" class="nav-link "><i
												class="ti ti-calendar-due me-2"></i>Leave & Attendance</a>
									</li>
									<li>
										<a href="{{url('teacher-salary')}}" class="nav-link "><i
												class="ti ti-report-money me-2"></i>Salary</a>
									</li>
									<li>
										<a href="{{url('teacher-library')}}" class="nav-link"><i
												class="ti ti-bookmark-edit me-2"></i>Library</a>
									</li>

								</ul>
								<!-- /List -->
								<div class="card">
									<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
										<h4 class="mb-3">Time Table</h4>
										<div class="d-flex align-items-center flex-wrap">
											<div class="dropdown mb-3">
												<a href="javascript:void(0);" class="btn btn-outline-light border-white bg-white dropdown-toggle shadow-md"  data-bs-toggle="dropdown"><i class="ti ti-calendar-due me-2"></i>This Year</a>
												<ul class="dropdown-menu p-3">
													<li>
														<a href="javascript:void(0);" class="dropdown-item rounded-1">
															This Year
														</a>
													</li>
													<li>
														<a href="javascript:void(0);" class="dropdown-item rounded-1">
															This Month
														</a>
													</li>
													<li>
														<a href="javascript:void(0);" class="dropdown-item rounded-1">
															This Week
														</a>
													</li>
												</ul>
											</div>
										</div>
									</div>
									<div class="card-body">		

										<div class="d-flex flex-nowrap overflow-auto">
											<div class="d-flex flex-column me-4 flex-fill">
												<div class="mb-3">
													<h6>Monday</h6>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<span class="text-dark d-block py-2">Class : III, A</span>
													<span class="text-dark d-block pb-2">Subject : Spanish</span>
													<p class="text-dark"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : I, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>03:15 - 04:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:107</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : V, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>11:30 - 12:15 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>02:15 - 03:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
											</div>
											<div class="d-flex flex-column me-4 flex-fill">
												<div class="mb-3">
													<h6>Tuesday</h6>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>02:15 - 03:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:107</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : V, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>11:30 - 12:15 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : I, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>03:15 - 04:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
											</div>
											<div class="d-flex flex-column me-4 flex-fill">
												<div class="mb-3">
													<h6>Wednesday</h6>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Computer</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
												</div>
				
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : II, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Science</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Maths</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Chemistry</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>11:30 - 12:15 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Physics</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:101</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Englishh</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>02:15 - 03:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>03:15 - 04:00 AM</p>
												</div>
											</div>
											<div class="d-flex flex-column me-4 flex-fill">
												<div class="mb-3">
													<h6>Thursday</h6>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Physics</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : II, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Science</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>11:30 - 12:15 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : I, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:101</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Chemistry</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>02:15 - 03:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Maths</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>03:15 - 04:00 AM</p>
												</div>
											</div>
											<div class="d-flex flex-column me-4 flex-fill">
												<div class="mb-3">
													<h6>Friday</h6>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:107</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : V, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>11:30 - 12:15 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>02:15 - 03:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : I, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>03:15 - 04:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
											</div>
											<div class="d-flex flex-column me-4 flex-fill">
												<div class="mb-3">
													<h6>Saturday</h6>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:106</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:107</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : V, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : English</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>11:30 - 12:15 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : IV, B</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>02:15 - 03:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:108</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : I, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>03:15 - 04:00 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
												<div class="rounded p-3 mb-4 border">
													<div class="pb-3 border-bottom">
														<span class="text-danger badge bg-transparent-danger text-nowrap ">Room No:104</span>
													</div>
													<p class="text-dark d-block py-2 m-0">Class : III, A</p>
													<p class="text-dark d-block pb-2 m-0">Subject : Spanish</p>
													<p class="text-dark text-nowrap m-0"><i class="ti ti-clock me-1"></i>09:45 -10:30 AM</p>
												</div>
											</div>
										</div>
									</div>
									<div class="card-footer border-0 pb-0">
										<div class="row">
											<div class="col-lg-4 col-xxl-4 col-xl-4 d-flex">
												<div class="card flex-fill">
													<div class="card-body bg-transparent-primary">
														<span class="bg-primary badge badge-sm mb-2">Morning Break</span>
														<p class="text-dark"><i class="ti ti-clock me-1"></i>10:30 to 10 :45 AM</p>
													</div>
												</div>
											</div>
											<div class="col-lg-4 col-xxl-3 d-flex">
												<div class="card flex-fill">
													<div class="card-body bg-transparent-warning">
														<span class="bg-warning badge badge-sm mb-2">Lunch</span>
														<p class="text-dark"><i class="ti ti-clock me-1"></i>10:30 to 10 :45 AM</p>
													</div>
												</div>
											</div>
											<div class="col-lg-4 col-xxl-3 d-flex">
												<div class="card flex-fill">
													<div class="card-body bg-transparent-info">
														<span class="bg-info badge badge-sm mb-2">Evening Break</span>
														<p class="text-dark"><i class="ti ti-clock me-1"></i>03:30 PM to 03:45 PM</p>
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
	<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
