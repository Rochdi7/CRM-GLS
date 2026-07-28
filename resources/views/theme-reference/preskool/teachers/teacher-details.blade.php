<?php $page = 'teacher-details'; ?>
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
								<a href="#" class="btn btn-light me-2 mb-2" data-bs-toggle="modal" data-bs-target="#login_detail"><i class="ti ti-lock me-2"></i>Login Details</a>					
								<a href="{{url('edit-teacher')}}" class="btn btn-primary d-flex align-items-center mb-2"><i class="ti ti-edit-circle me-2"></i>Edit Teacher</a> 
							</div>
						</div>
					</div>
					<!-- /Page Header -->

					<!-- Student Information -->
					<div class="col-xxl-3 col-xl-4 theiaStickySidebar">
						<div class="card border-white">
							<div class="card-header">
								<div class="d-flex align-items-center flex-wrap row-gap-3">                                                
									<div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
										<img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="img-fluid" alt="img">
									</div>                                              
									<div>
										<h5 class="mb-1 mb-1 text-truncate">Teresa</h5>
										<p class="text-primary mb-1">T849126</p>
										<p>Joined : 25 May 24</p>
									</div>
								</div>
							</div>
							<div class="card-body">
								<h5 class="mb-3">Basic Information</h5>
								<dl class="row mb-0"> 
									<dt class="col-6 fw-medium text-dark mb-3">Class & Section</dt> 
									<dd class="col-6  mb-3">III, A</dd> 
									<dt class="col-6 fw-medium text-dark mb-3">Subject</dt> 
									<dd class="col-6  mb-3">Physics</dd> 
									<dt class="col-6 fw-medium text-dark mb-3">Gender</dt> 
									<dd class="col-6  mb-3">Female</dd> 
									<dt class="col-6 fw-medium text-dark mb-3">Blood Group</dt> 
									<dd class="col-6  mb-3">O +ve</dd> 
									<dt class="col-6 fw-medium text-dark mb-3">House</dt> 
									<dd class="col-6  mb-3">Red</dd> 
									<dt class="col-6 fw-medium text-dark mb-3">Language Known</dt> 
									<dd class="col-6  mb-3">English</dd> 
									<dt class="col-6 fw-medium text-dark mb-0">Language</dt> 
									<dd class="col-6  mb-0"><span class="badge badge-light text-dark me-2">English</span><span class="badge badge-light text-dark">Spanish</span></dd> 
								</dl>								
							</div>
						</div>
						<div class="card border-white">
							<div class="card-body">
								<h5 class="mb-3 ">Primary Contact Info</h5>
								<div class="d-flex align-items-center mb-3">
									<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-phone"></i></span>
									<div>
										<span class=" text-dark fw-medium mb-1">Phone Number</span>
										<p>+1 46548 84498</p>
									</div>
								</div>
								<div class="d-flex align-items-center">
									<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-mail"></i></span>
									<div>
										<span class="text-dark fw-medium mb-1">Email Address</span>
										<p>jan@example.com</p>
									</div>
								</div>
							</div>
						</div>
						<div class="card border-white">
							<div class="card-body pb-1">
								<h5 class="mb-3">PAN Number / ID Number</h5>
								<div class="d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center mb-3">
										<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-id"></i></span>
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
									<li class="nav-item"><a class="nav-link active" href="#hostel" data-bs-toggle="tab">Hostel</a></li>
									<li class="nav-item"><a class="nav-link" href="#transport" data-bs-toggle="tab">Transportation</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade show active" id="hostel">
										<div class="d-flex align-items-center mb-3">
											<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-building-fortress fs-16"></i></span>
											<div>
												<h6 class="mb-1">HI-Hostel, Floor</h6>
												<p class="text-primary">Room No : 25</p>
											</div>
										</div>
									</div>
									<div class="tab-pane fade" id="transport">
										<div class="d-flex align-items-center mb-3">
											<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-bus fs-16"></i></span>
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
					<!-- /Student Information -->

					<div class="col-xxl-9 col-xl-8">
						<div class="row">
							<div class="col-md-12">

								<!-- List -->
								<ul class="nav nav-tabs nav-tabs-bottom mb-4">
									<li>
										<a href="{{url('teacher-details')}}" class="nav-link active"><i class="ti ti-school me-2"></i>Teacher Details</a>
									</li>
									<li>
										<a href="{{url('routine-teachers')}}" class="nav-link"><i class="ti ti-table-options me-2"></i>Routine</a>
									</li>
									<li>
										<a href="{{url('teacher-leaves')}}" class="nav-link"><i class="ti ti-calendar-due me-2"></i>Leave & Attendance</a>
									</li>
									<li>
										<a href="{{url('teacher-salary')}}" class="nav-link"><i class="ti ti-report-money me-2"></i>Salary</a>
									</li>
									<li>
										<a href="{{url('teacher-library')}}" class="nav-link"><i class="ti ti-bookmark-edit me-2"></i>Library</a>
									</li>
									
								</ul>
								<!-- /List -->

								<!-- Parents Information -->
								<div class="card">
									<div class="card-header">
										<h5>Profile Details</h5>
									</div>
									<div class="card-body">
										<div class="border rounded p-3 pb-0">									
											<div class="row">									
												
												<div class="col-sm-6 col-lg-4">
													<div class="mb-3">
														<p class="text-dark fw-medium mb-1">Father’s Name</p>
														<p>Francis Saviour</p>
													</div>
												</div>
												<div class="col-sm-6 col-lg-4">
													<div class="mb-3">
														<p class="text-dark fw-medium mb-1">Mother Name</p>
														<p>Stella Bruce</p>
													</div>
												</div>
												<div class="col-sm-6 col-lg-4">
													<div class="mb-3">
														<p class="text-dark fw-medium mb-1">DOB</p>
														<p>25 Jan 1992</p>
													</div>
												</div>
												<div class="col-sm-6 col-lg-4">
													<div class="mb-3">
														<p class="text-dark fw-medium mb-1">Martial Status</p>
														<p>Single</p>
													</div>
												</div>
												<div class="col-sm-6 col-lg-4">
													<div class="mb-3">
														<p class="text-dark fw-medium mb-1">Qualification</p>
														<p>MBA</p>
													</div>
												</div>
												<div class="col-sm-6 col-lg-4">
													<div class="mb-3">
														<p class="text-dark fw-medium mb-1">Experience</p>
														<p>2  Years</p>
													</div>
												</div>
												
											</div>
										</div>
										
									</div>
								</div>
								<!-- /Parents Information -->

							</div>

							<!-- Documents -->
							<div class="col-xxl-6 d-flex">
								<div class="card flex-fill">
									<div class="card-header">
										<h5>Documents</h5>
									</div>
									<div class="card-body">
										<div class="bg-light-300 border rounded d-flex align-items-center justify-content-between mb-3 p-2">
											<div class="d-flex align-items-center overflow-hidden">
												<span class="avatar avatar-md bg-white rounded flex-shrink-0 text-default"><i class="ti ti-pdf fs-15"></i></span>
												<div class="ms-2">
													<p class="text-truncate fw-medium text-dark">Resume.pdf</p>
												</div>
											</div>
											<a href="#" class="btn btn-dark btn-icon btn-sm"><i class="ti ti-download"></i></a>
										</div>
										<div class="bg-light-300 border rounded d-flex align-items-center justify-content-between p-2">
											<div class="d-flex align-items-center overflow-hidden">
												<span class="avatar avatar-md bg-white rounded flex-shrink-0 text-default"><i class="ti ti-pdf fs-15"></i></span>
												<div class="ms-2">
													<p class="text-truncate fw-medium text-dark">Joining Letter.pdf</p>
												</div>
											</div>
											<a href="#" class="btn btn-dark btn-icon btn-sm"><i class="ti ti-download"></i></a>
										</div>
									</div>
								</div>
							</div>
							<!-- /Documents -->

							<!-- Address -->
							<div class="col-xxl-6 d-flex">
								<div class="card flex-fill">
									<div class="card-header">
										<h5>Address</h5>
									</div>
									<div class="card-body">
										<div class="d-flex align-items-center mb-3">
											<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-map-pin-up"></i></span>
											<div>
												<p class="text-dark fw-medium mb-1">Current Address</p>
												<p>3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
											</div>
										</div>
										<div class="d-flex align-items-center">
											<span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-map-pins"></i></span>
											<div>
												<p class="text-dark fw-medium mb-1">Permanent Address</p>
												<p>3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
											</div>
										</div>
									</div>
								</div>								
							</div>
							<!-- /Address -->

							<!-- Previous School Details -->
							<div class="col-xxl-12">
								<div class="card">
									<div class="card-header">
										<h5>Previous School Details</h5>
									</div>
									<div class="card-body pb-1">
										<div class="row">
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Previous School Name</p>
													<p>Oxford Matriculation, USA</p>
												</div>
											</div>
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">School Address</p>
													<p>1852 Barnes Avenue, Cincinnati, OH 45202</p>
												</div>
											</div>
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Phone Number</p>
													<p>+1 35676 45556</p>
												</div>
											</div>
											
										</div>
									</div>
								</div>
							</div>
							<!-- /Previous School Details -->

							<!-- Bank Details -->
							<div class="col-xxl-6 d-flex">
								<div class="card flex-fill">
									<div class="card-header">
										<h5>Bank Details</h5>
									</div>
									<div class="card-body pb-1">
										<div class="row">
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Bank Name</p>
													<p>Bank of America</p>
												</div>
											</div>
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Branch</p>
													<p>Cincinnati</p>
												</div>
											</div>
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">IFSC</p>
													<p>BOA83209832</p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<!-- /Bank Details -->

							<!-- Medical History -->
							<div class="col-xxl-6 d-flex">
								<div class="card flex-fill">
									<div class="card-header">
										<h5>Work Details</h5>
									</div>
									<div class="card-body pb-1">
										<div class="row">
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Contract Type</p>
													<p>Permanent</p>
												</div>
											</div>
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Shift</p>
													<p>Morning</p>
												</div>
											</div>
											<div class="col-md-4">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Work Location</p>
													<p>2nd Floor</p>
												</div>
											</div>
										</div>
									</div>
								</div>								
							</div>
							<!-- /Medical History -->

							<!-- Medical History -->
							<div class="col-xxl-12 d-flex">
								<div class="card flex-fill">
									<div class="card-header">
										<h5>Social Media</h5>
									</div>
									<div class="card-body pb-1">
										<div class="row row-cols-xxl-5 row-cols-xl-3">
											<div class="col">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Facebook</p>
													<p>www.facebook.com</p>
												</div>
											</div>
											<div class="col">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Twitter</p>
													<p>www.twitter.com</p>
												</div>
											</div>
											<div class="col">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Linkedin</p>
													<p>www.linkedin.com</p>
												</div>
											</div>
											<div class="col">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Youtube</p>
													<p>www.youtube.com</p>
												</div>
											</div>
											<div class="col">
												<div class="mb-3">
													<p class="mb-1 text-dark fw-medium">Instagram</p>
													<p>www.instagram.com</p>
												</div>
											</div>
										</div>
									</div>
								</div>								
							</div>
							<!-- /Medical History -->

							<!-- Other Info -->
							<div class="col-xxl-12">
								<div class="card">
									<div class="card-header">
										<h5>Other Info</h5>
									</div>
									<div class="card-body">
										<p>Depending on the specific needs of your organization or system, additional information may be collected or tracked. It's important to ensure that any data collected complies with privacy regulations and policies to protect students' sensitive information.</p>
									</div>
								</div>
							</div>
							<!-- /Other Info -->

						</div>
					</div>

				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
